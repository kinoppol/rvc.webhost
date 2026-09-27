<?php
declare(strict_types=1);

/**
 * Minimal migration runner.
 *
 * Each file in /migrations is named YYYY_MM_DD_HHMMSS_description.php and returns:
 *   ['description' => '...', 'up' => [sql, ...] | fn(PDO $db), 'down' => [sql, ...] | fn(PDO $db)]
 * MariaDB DDL auto-commits, so migrations should be written to be safe to re-run
 * (CREATE TABLE IF NOT EXISTS, ADD COLUMN IF NOT EXISTS, ...).
 */
final class Migrator
{
    public function __construct(private PDO $db, private string $path = MIGRATIONS_PATH)
    {
    }

    public function ensureTable(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(191) NOT NULL UNIQUE,
            batch INT UNSIGNED NOT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    /** @return string[] migration names sorted ascending */
    public function files(): array
    {
        $out = [];
        foreach (glob($this->path . '/*.php') ?: [] as $f) $out[] = basename($f, '.php');
        sort($out, SORT_STRING);
        return $out;
    }

    /** @return array<string,array{batch:int,applied_at:string}> */
    public function applied(): array
    {
        $this->ensureTable();
        $rows = $this->db->query('SELECT migration, batch, applied_at FROM migrations ORDER BY id')->fetchAll();
        $out = [];
        foreach ($rows as $r) $out[$r['migration']] = ['batch' => (int)$r['batch'], 'applied_at' => $r['applied_at']];
        return $out;
    }

    public function status(): array
    {
        $applied = $this->applied();
        $rows = [];
        foreach ($this->files() as $name) {
            $def = $this->load($name);
            $rows[] = [
                'name'        => $name,
                'description' => $def['description'] ?? '',
                'applied'     => isset($applied[$name]),
                'batch'       => $applied[$name]['batch'] ?? null,
                'applied_at'  => $applied[$name]['applied_at'] ?? null,
                'has_down'    => array_key_exists('down', $def),
                'missing'     => false,
            ];
        }
        // Applied in DB but file removed from disk
        foreach ($applied as $name => $a) {
            if (!in_array($name, $this->files(), true)) {
                $rows[] = ['name' => $name, 'description' => '(ไม่พบไฟล์)', 'applied' => true, 'batch' => $a['batch'],
                    'applied_at' => $a['applied_at'], 'has_down' => false, 'missing' => true];
            }
        }
        return $rows;
    }

    public function pending(): array
    {
        $applied = $this->applied();
        return array_values(array_filter($this->files(), fn($n) => !isset($applied[$n])));
    }

    /** Runs all pending migrations (or only $only). Returns names applied. */
    public function migrate(?string $only = null): array
    {
        $pending = $this->pending();
        if ($only !== null) $pending = array_values(array_intersect($pending, [$only]));
        if (!$pending) return [];
        $batch = (int)$this->db->query('SELECT COALESCE(MAX(batch),0) FROM migrations')->fetchColumn() + 1;
        $done = [];
        foreach ($pending as $name) {
            $this->run($this->load($name)['up'] ?? []);
            $this->db->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)')->execute([$name, $batch]);
            $done[] = $name;
        }
        return $done;
    }

    /** Rolls back the latest batch (or a single named migration). Returns names rolled back. */
    public function rollback(?string $only = null): array
    {
        $applied = $this->applied();
        if (!$applied) return [];
        if ($only !== null) {
            $targets = isset($applied[$only]) ? [$only] : [];
        } else {
            $last = max(array_column($applied, 'batch'));
            $targets = array_keys(array_filter($applied, fn($a) => $a['batch'] === $last));
        }
        rsort($targets, SORT_STRING);
        $done = [];
        foreach ($targets as $name) {
            if (!is_file($this->path . "/$name.php")) throw new RuntimeException("ไม่พบไฟล์ migration: $name");
            $def = $this->load($name);
            if (!array_key_exists('down', $def)) throw new RuntimeException("migration $name ไม่มีคำสั่ง down จึงย้อนกลับไม่ได้");
            $this->run($def['down']);
            $this->db->prepare('DELETE FROM migrations WHERE migration = ?')->execute([$name]);
            $done[] = $name;
        }
        return $done;
    }

    /** Creates a new, empty migration file. Returns the file name. */
    public function create(string $description): string
    {
        $slug = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($description)), '_') ?: 'change';
        $name = date('Y_m_d_His') . '_' . $slug;
        $tpl = "<?php\n// " . str_replace(["\r", "\n"], ' ', $description) . "\nreturn [\n"
            . "    'description' => " . var_export($description, true) . ",\n"
            . "    'up' => [\n        // 'ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL',\n    ],\n"
            . "    'down' => [\n        // 'ALTER TABLE users DROP COLUMN IF EXISTS phone',\n    ],\n];\n";
        if (file_put_contents($this->path . "/$name.php", $tpl) === false) {
            throw new RuntimeException('เขียนไฟล์ในโฟลเดอร์ migrations ไม่ได้ (ตรวจสอบสิทธิ์การเขียน)');
        }
        return $name;
    }

    private function load(string $name): array
    {
        $file = $this->path . "/$name.php";
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name) || !is_file($file)) return [];
        $def = require $file;
        return is_array($def) ? $def : [];
    }

    private function run(array|callable $step): void
    {
        if (is_callable($step)) {
            $step($this->db);
            return;
        }
        foreach ($step as $sql) $this->db->exec($sql);
    }
}
