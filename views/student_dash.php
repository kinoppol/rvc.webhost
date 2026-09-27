<?php
$key = user_status_key($u);
$pct = usage_pct($u);
$st = status_cfg($key);
$boxBg = ['ok' => '#146c43', 'warn' => '#997404', 'over' => '#b02a37', 'suspended' => '#b02a37', 'pending' => '#6c757d'][$key];
$active = $u['status'] === 'active';
?>
<?php if ($u['status'] === 'suspended'): ?>
  <div class="alert danger"><i class="bi bi-pause-circle"></i><span>บัญชีของคุณถูกระงับชั่วคราว — เข้าใช้ SFTP และฐานข้อมูลไม่ได้ กรุณาติดต่อผู้ดูแลระบบ</span></div>
<?php endif ?>
<div class="grid-auto">
  <div class="kpi" style="background:<?= $boxBg ?>"><div><div class="n"><?= $pct ?>%</div><div class="small">ใช้พื้นที่ไปแล้ว</div></div><i class="bi bi-pie-chart-fill"></i></div>
  <div class="kpi" style="background:#0f2b5c"><div><div class="n"><?= $active ? 'ออนไลน์' : 'ระงับ' ?></div><div class="small">สถานะเว็บไซต์</div></div><i class="bi bi-globe2"></i></div>
  <div class="kpi" style="background:#163a78"><div><div class="n"><?= $active ? 'พร้อมใช้' : 'ล็อก' ?></div><div class="small">ฐานข้อมูล MariaDB</div></div><i class="bi bi-database-fill"></i></div>
</div>

<div class="grid-2" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,380px),1fr))">
  <div class="panel t-navy">
    <div class="panel-h"><h2>โควตาพื้นที่ (Quota)</h2><span class="pill <?= $st['cls'] ?>"><?= $st['label'] ?></span></div>
    <div class="panel-b stack" style="gap:14px">
      <div style="display:flex;align-items:baseline;gap:8px"><span style="font-size:34px;font-weight:700;color:#0a1f44"><?= e(rtrim(rtrim(number_format((float)$u['disk_used_mb'], 1), '0'), '.')) ?></span><span class="muted">/ <?= (int)$u['quota_soft_mb'] ?> MB</span></div>
      <div class="bar thick"><div class="<?= $key === 'ok' ? 'ok' : ($key === 'warn' ? 'warn' : 'over') ?>" style="width:<?= min($pct, 100) ?>%"></div></div>
      <div class="small muted" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span>Soft limit <?= (int)$u['quota_soft_mb'] ?> MB · Hard limit <?= (int)$u['quota_hard_mb'] ?> MB</span>
        <span><i class="bi bi-clock-history"></i> อัปเดตล่าสุดเมื่อ <?= $u['disk_synced_at'] ? e(thai_date($u['disk_synced_at'], true)) : 'ยังไม่มีข้อมูล' ?></span>
      </div>
      <?php if ($key === 'warn'): ?>
        <div class="alert warn"><i class="bi bi-exclamation-triangle-fill"></i><span>พื้นที่ใกล้เต็ม ควรลบไฟล์ที่ไม่ใช้ออกก่อนถึง Hard limit</span></div>
      <?php elseif ($key === 'over'): ?>
        <div class="alert danger"><i class="bi bi-exclamation-triangle-fill"></i><span>ใช้พื้นที่เกิน Soft limit แล้ว หากถึง Hard limit จะบันทึกไฟล์เพิ่มไม่ได้ กรุณาลบไฟล์ที่ไม่ใช้หรือติดต่อผู้ดูแลระบบ</span></div>
      <?php endif ?>
      <span class="xs muted2">ข้อมูลการใช้พื้นที่ซิงก์จากเซิร์ฟเวอร์ทุก 15 นาที</span>
    </div>
  </div>

  <div class="panel t-blue">
    <div class="panel-h"><h2>ข้อมูลเชื่อมต่อ</h2></div>
    <div class="panel-l">
      <?php foreach ($rows as [$k, $v]): ?>
        <div class="conn-row"><span class="k" style="width:130px"><?= e($k) ?></span><span class="v" style="font-size:13px"><?= e($v) ?></span>
          <button type="button" class="icon-btn" data-copy="<?= e($v) ?>" title="คัดลอก"><i class="bi bi-clipboard"></i></button></div>
      <?php endforeach ?>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-h"><h2>ทางลัด</h2></div>
  <div class="panel-b" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;padding:16px 20px">
    <a class="shortcut" href="<?= e(url('settings')) ?>#ssh"><i class="bi bi-key"></i>เปลี่ยนรหัสผ่าน SSH/SFTP</a>
    <a class="shortcut" href="<?= e(url('settings')) ?>#db"><i class="bi bi-database-lock"></i>เปลี่ยนรหัสผ่านฐานข้อมูล</a>
    <a class="shortcut" href="<?= e(url('guide')) ?>"><i class="bi bi-file-earmark-arrow-down"></i>ดาวน์โหลดคู่มือเชื่อมต่อ FileZilla</a>
  </div>
</div>
