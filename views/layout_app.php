<?php
$me = current_user();
$isAdmin = $me['role'] === 'admin';
$nav = $nav ?? '';
$pc = $isAdmin ? pending_counts() : [];
$items = $isAdmin
    ? [['admin', 'ภาพรวมระบบ', 'bi-speedometer2', 0], ['admin_users', 'จัดการผู้ใช้', 'bi-people', 0],
       ['admin_runbook', 'เช็กลิสต์ติดตั้งเซิร์ฟเวอร์', 'bi-list-check', 0], ['admin_migrations', 'Migrations', 'bi-database-gear', $pc['migrations']],
       ['admin_settings', 'ตั้งค่าระบบ', 'bi-sliders', 0], ['admin_audit', 'บันทึกการใช้งาน', 'bi-journal-text', 0]]
    : [['dash', 'แดชบอร์ด', 'bi-speedometer2', 0], ['settings', 'ตั้งค่าบัญชี', 'bi-gear', 0]];
$roleLabel = $isAdmin ? 'ผู้ดูแลระบบ' : 'นักศึกษา';
$lastSync = Settings::get('last_usage_sync');
require __DIR__ . '/_head.php';
?>
<body>
<div class="app">
  <aside class="side">
    <a class="side-brand" href="<?= e(url('landing')) ?>"><img src="<?= e(base_url('assets/vec_logo.png')) ?>" alt="โลโก้ สอศ." class="logo-38">Hosting Portal</a>
    <div class="side-me">
      <span class="avatar"><i class="bi <?= $isAdmin ? 'bi-shield-lock' : 'bi-person' ?>"></i></span>
      <div style="display:flex;flex-direction:column;line-height:1.3;min-width:0">
        <span style="color:#fff;font-size:14px;overflow:hidden;text-overflow:ellipsis"><?= e($me['full_name'] ?: $me['username']) ?></span>
        <span class="xs" style="color:#9fb3d9"><?= $roleLabel ?></span>
      </div>
    </div>
    <nav>
      <span class="navlbl">เมนูหลัก</span>
      <?php foreach ($items as [$k, $label, $icon, $count]): ?>
        <a href="<?= e(url($k)) ?>" class="<?= $nav === $k ? 'on' : '' ?>" title="<?= e($label) ?>"><i class="bi <?= $icon ?>"></i><?= e($label) ?><?php if ($count): ?><span class="count"><?= (int)$count ?></span><?php endif ?></a>
      <?php endforeach ?>
    </nav>
    <form method="post" action="<?= e(url('logout')) ?>" class="side-foot"><?= csrf_field() ?>
      <button class="logout"><i class="bi bi-box-arrow-left"></i>ออกจากระบบ</button>
    </form>
  </aside>
  <div class="main">
    <header class="topbar">
      <button class="toggle" data-toggle-side aria-label="ย่อ/ขยายเมนู"><i class="bi bi-list"></i></button>
      <a href="<?= e(url('landing')) ?>" class="muted small">หน้าแรก</a>
      <span class="grow"></span>
      <span class="small muted" style="display:flex;gap:6px;align-items:center"><i class="bi bi-arrow-repeat"></i>ซิงก์ข้อมูลล่าสุด <?= $lastSync ? e(date('H:i', strtotime($lastSync))) . ' น.' : '—' ?></span>
      <span class="sep"></span>
      <span class="mono" style="font-size:14px"><?= e($me['username']) ?></span>
    </header>
    <div class="page-head">
      <h1><?= e($title ?? '') ?></h1>
      <span class="small muted"><?= $roleLabel ?> / <span style="color:#1b2433"><?= e($title ?? '') ?></span></span>
    </div>
    <main class="content">
<?= $content ?>
    </main>
  </div>
</div>
<?php require __DIR__ . '/_overlays.php'; ?>
</body>
</html>
