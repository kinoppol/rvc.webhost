<?php
$decor = array_map(fn($u) => $u + ['key' => user_status_key($u), 'pct' => usage_pct($u)], $users);
$active = array_filter($decor, fn($u) => in_array($u['status'], ['active', 'suspended'], true));
$overN = count(array_filter($decor, fn($u) => $u['key'] === 'over'));
$pendN = count(array_filter($decor, fn($u) => $u['key'] === 'pending'));
$usedGb = array_sum(array_column($active, 'disk_used_mb')) / 1024;
$limitGb = max(1, Settings::int('disk_total_gb'));
$barColor = fn(float $r) => $r >= .9 ? 'over' : ($r >= .75 ? 'warn' : 'blue');

$cap = [['จำนวนผู้ใช้', $seats['used'] . ' / ' . $seats['max'], $seats['used'] / max(1, $seats['max'])],
        ['พื้นที่ดิสก์ (โควตาที่ใช้)', sprintf('%.1f / %d GB', $usedGb, $limitGb), $usedGb / $limitGb]];
$mem = @is_readable('/proc/meminfo') ? (string)@file_get_contents('/proc/meminfo') : '';
if (preg_match('/MemTotal:\s+(\d+)/', $mem, $t) && preg_match('/MemAvailable:\s+(\d+)/', $mem, $a)) {
    $cap[] = ['หน่วยความจำ (RAM)', sprintf('%.1f / %.1f GB', ($t[1] - $a[1]) / 1048576, $t[1] / 1048576), ($t[1] - $a[1]) / $t[1]];
}
$order = ['over' => 0, 'warn' => 1, 'suspended' => 2, 'pending' => 3, 'ok' => 4];
$attention = array_filter($decor, fn($u) => $u['key'] !== 'ok');
usort($attention, fn($x, $y) => $order[$x['key']] <=> $order[$y['key']]);

$rbLeft = $rb['total'] - $rb['done'];
$rbPct = (int)round($rb['done'] / max(1, $rb['total']) * 100);
$rbPending = [];
foreach ($rb['sections'] as $s) foreach ($s['items'] as $it) if (!$it['done']) $rbPending[] = $it + ['sec' => $s['no']];
$rbIndex = [];
foreach ($rb['sections'] as $s) foreach ($s['items'] as $it) $rbIndex[$it['key']] = $it;
$pendingMig = pending_counts()['migrations'];

$maxH = max($limitGb * 1.07, ...array_map(fn($h) => $h['used_mb'] / 1024, $hist ?: [['used_mb' => 0]]));
$stateIcon = ['pass' => ['bi-check-circle-fill', 'c-ok', 'ทำงานปกติ'], 'fail' => ['bi-x-circle-fill', 'c-bad', 'มีปัญหา'], 'unknown' => ['bi-question-circle-fill', 'muted2', 'ตรวจไม่ได้'], 'manual' => ['bi-dash-circle', 'muted2', '-']];
?>

<?php if ($pendingMig): ?>
  <div class="alert warn"><i class="bi bi-database-exclamation"></i><span>มี migration ที่ยังไม่ได้รัน <?= $pendingMig ?> รายการ — <a href="<?= e(url('admin_migrations')) ?>">ไปที่หน้า Migrations</a></span></div>
<?php endif ?>

<div class="panel <?= $rbLeft ? 't-warn' : 't-ok' ?>">
  <div class="panel-h">
    <h2><i class="bi bi-list-check c-blue"></i>ความพร้อมของเซิร์ฟเวอร์ตามคู่มือติดตั้ง</h2>
    <a href="<?= e(url('admin_runbook')) ?>" class="small">เปิดเช็กลิสต์ทั้งหมด <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="panel-b stack" style="gap:12px">
    <div style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:8px">
      <span><strong style="font-size:22px;color:#0a1f44"><?= $rb['done'] ?> / <?= $rb['total'] ?></strong> <span class="muted">รายการเสร็จแล้ว</span></span>
      <?php if ($rbLeft): ?><span class="pill warn">เหลืออีก <?= $rbLeft ?> รายการ</span><?php else: ?><span class="pill ok">พร้อมใช้งานเต็มรูปแบบ</span><?php endif ?>
    </div>
    <div class="bar mid"><div class="<?= $rbLeft ? 'warn' : 'ok' ?>" style="width:<?= $rbPct ?>%"></div></div>
    <?php if ($rbPending): ?>
      <div class="small" style="display:flex;flex-direction:column;gap:6px;margin-top:4px">
        <span class="muted">สิ่งที่ต้องดำเนินการต่อ:</span>
        <?php foreach (array_slice($rbPending, 0, 6) as $it): ?>
          <a href="<?= e(url('admin_runbook')) ?>#<?= e($it['key']) ?>" style="display:flex;gap:8px;align-items:flex-start;color:#1b2433">
            <i class="bi <?= $it['state'] === 'fail' ? 'bi-x-circle-fill c-bad' : 'bi-circle muted2' ?>" style="margin-top:2px"></i>
            <span><span class="mono muted2"><?= e($it['key']) ?></span> <?= e($it['title']) ?></span>
          </a>
        <?php endforeach ?>
        <?php if (count($rbPending) > 6): ?><span class="muted2">และอีก <?= count($rbPending) - 6 ?> รายการ…</span><?php endif ?>
      </div>
    <?php endif ?>
  </div>
</div>

<div class="grid-auto">
  <?php foreach ([
      ['ผู้ใช้ทั้งหมด', $seats['used'] . ' / ' . $seats['max'], $seats['open'] ? 'รองรับได้อีก ' . $seats['left'] . ' บัญชี' : 'ปิดรับสมัคร', 'bi-people-fill', '#0a1f44'],
      ['พื้นที่ดิสก์รวม', sprintf('%.1f / %d GB', $usedGb, $limitGb), 'ใช้ไป ' . round($usedGb / $limitGb * 100) . '%', 'bi-hdd-fill', '#163a78'],
      ['เกินโควตา', $overN . ' บัญชี', 'ควรแจ้งเตือนผู้ใช้', 'bi-exclamation-octagon-fill', '#dc3545'],
      ['รอยืนยันอีเมล', $pendN . ' บัญชี', 'ยังไม่ได้สร้างทรัพยากร', 'bi-hourglass-split', '#6c757d'],
  ] as [$l, $v, $sub, $ic, $bg]): ?>
    <div class="stat"><span class="ic" style="background:<?= $bg ?>"><i class="bi <?= $ic ?>"></i></span>
      <div style="display:flex;flex-direction:column;gap:2px;min-width:0"><span class="small muted"><?= e($l) ?></span><span class="v"><?= e($v) ?></span><span class="xs muted2"><?= e($sub) ?></span></div></div>
  <?php endforeach ?>
</div>

<div class="grid-2" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr))">
  <div class="panel t-navy">
    <div class="panel-h"><h2>การใช้ดิสก์รวม 14 วันล่าสุด (GB)</h2>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="sync"><button class="btn sm"><i class="bi bi-arrow-repeat"></i>ซิงก์ตอนนี้</button></form>
    </div>
    <div class="panel-b">
      <?php if (!$hist): ?>
        <div class="empty">ยังไม่มีข้อมูล — ตั้ง cron <code>cron/sync_usage.php</code> หรือกด "ซิงก์ตอนนี้"</div>
      <?php else: ?>
        <div class="chart">
          <div class="limit" style="bottom:<?= $limitGb / $maxH * 100 ?>%"><span>ขีดจำกัด <?= $limitGb ?> GB</span></div>
          <?php foreach ($hist as $i => $h): $gb = $h['used_mb'] / 1024; ?>
            <div class="col-bar <?= $i === count($hist) - 1 ? 'last' : '' ?>" title="<?= e(thai_date($h['day'])) ?>: <?= sprintf('%.2f', $gb) ?> GB · <?= (int)$h['users_count'] ?> บัญชี" style="height:<?= $gb / $maxH * 100 ?>%"></div>
          <?php endforeach ?>
        </div>
        <div class="xs muted2" style="display:flex;justify-content:space-between;margin-top:6px;padding-left:6px">
          <span><?= e(thai_date($hist[0]['day'])) ?></span><span><?= e(thai_date(end($hist)['day'])) ?></span>
        </div>
      <?php endif ?>
    </div>
  </div>

  <div class="col">
    <div class="panel t-blue">
      <div class="panel-h"><h2>ความจุของระบบ (Capacity)</h2></div>
      <div class="panel-b stack" style="gap:18px">
        <?php foreach ($cap as [$l, $v, $r]): ?>
          <div style="display:flex;flex-direction:column;gap:6px">
            <div style="display:flex;justify-content:space-between;font-size:14px"><span><?= e($l) ?></span><strong><?= e($v) ?></strong></div>
            <div class="bar mid"><div class="<?= $barColor($r) ?>" style="width:<?= min(100, $r * 100) ?>%"></div></div>
          </div>
        <?php endforeach ?>
        <span class="xs muted2">ขีดจำกัดตั้งค่าได้ที่เมนูตั้งค่าระบบ (อ้างอิงไฟล์ Capacity Planning)</span>
      </div>
    </div>
    <div class="panel">
      <div class="panel-h"><h2>บริการบนเซิร์ฟเวอร์</h2><span class="xs muted2">โหมด: <?= Provisioner::isExec() ? 'exec (เซิร์ฟเวอร์จริง)' : 'simulate (ทดสอบ)' ?></span></div>
      <div class="panel-l">
        <?php foreach (['2.services' => 'Apache / MariaDB / OpenSSH', '3.repquota' => 'ระบบโควตา (repquota)', '6.sudoers' => 'สิทธิ์เรียกสคริปต์ Provisioning', '8.sync_cron' => 'Cron ซิงก์โควตา (ทุก 15 นาที)'] as $k => $label):
            $it = $rbIndex[$k]; [$ic, $cls, $txt] = $stateIcon[$it['state']]; ?>
          <div class="list-row small" style="font-size:14px;padding:9px 0" title="<?= e($it['msg']) ?>">
            <span class="grow"><?= e($label) ?></span><span class="<?= $cls ?>" style="display:flex;gap:6px;align-items:center"><i class="bi <?= $ic ?>"></i><?= $txt ?></span>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-h"><h2>ผู้ใช้ที่ต้องดำเนินการ</h2><a href="<?= e(url('admin_users')) ?>" class="small">ดูผู้ใช้ทั้งหมด <i class="bi bi-arrow-right"></i></a></div>
  <div class="panel-l">
    <?php if (!$attention): ?><div class="empty">ไม่มีผู้ใช้ที่ต้องดำเนินการ</div><?php endif ?>
    <?php foreach (array_slice($attention, 0, 10) as $u): $st = status_cfg($u['key']); ?>
      <div class="list-row">
        <span class="mono" style="font-size:14px;width:120px"><?= e($u['username']) ?></span>
        <span class="grow small muted" style="min-width:140px;font-size:14px"><?= e($u['full_name']) ?></span>
        <span class="small muted"><?= e($u['disk_used_mb']) ?> / <?= (int)$u['quota_soft_mb'] ?> MB</span>
        <span class="pill <?= $st['cls'] ?>"><?= $st['label'] ?></span>
        <a class="btn sm" href="<?= e(url('admin_user', ['u' => $u['username']])) ?>">จัดการ</a>
      </div>
    <?php endforeach ?>
  </div>
</div>
