<?php
$vals = Settings::all();
$choices = ['provision_mode' => ['simulate' => 'simulate — ทดสอบ (ไม่แตะเซิร์ฟเวอร์)', 'exec' => 'exec — เรียกสคริปต์บนเซิร์ฟเวอร์จริง'],
            'otp_mode' => ['mail' => 'mail — ส่งทางอีเมล', 'display' => 'display — แสดงบนจอ (ทดสอบ)'],
            'registration_open' => ['1' => 'เปิดรับสมัคร', '0' => 'ปิดรับสมัคร']];
$groups = ['บริการและโดเมน' => ['domain', 'sftp_host', 'email_domain', 'mail_from', 'otp_mode'],
           'ความจุและโควตา' => ['registration_open', 'max_users', 'disk_total_gb', 'default_soft_mb', 'default_hard_mb', 'courses'],
           'การเชื่อมต่อเซิร์ฟเวอร์' => ['provision_mode', 'script_dir', 'home_path']];
?>
<form method="post" class="col">
  <?= csrf_field() ?>
  <?php foreach ($groups as $g => $keys): ?>
    <div class="panel t-navy">
      <div class="panel-h"><h2><?= e($g) ?></h2></div>
      <div class="panel-b" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr));gap:18px">
        <?php foreach ($keys as $k): [, $label, $help] = Settings::DEFS[$k]; ?>
          <label><?= e($label) ?>
            <?php if (isset($choices[$k])): ?>
              <select name="<?= $k ?>"><?php foreach ($choices[$k] as $v => $t): ?><option value="<?= e($v) ?>" <?= $vals[$k] === (string)$v ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach ?></select>
            <?php else: ?>
              <input name="<?= $k ?>" value="<?= e($vals[$k]) ?>" class="<?= in_array($k, ['script_dir', 'home_path', 'domain', 'sftp_host'], true) ? 'mono' : '' ?>">
            <?php endif ?>
            <?php if ($help): ?><span class="hint"><?= e($help) ?></span><?php endif ?>
          </label>
        <?php endforeach ?>
      </div>
    </div>
  <?php endforeach ?>
  <div class="alert warn"><i class="bi bi-exclamation-triangle"></i><span>ก่อนเปลี่ยนเป็นโหมด <strong>exec</strong> ให้ทำเช็กลิสต์หัวข้อ 3–6 ให้ครบ (โควตา, sftp-users, สคริปต์ใน <?= e($vals['script_dir']) ?> และ sudoers) มิฉะนั้นการสมัครจะล้มเหลว</span></div>
  <div class="row-end"><button class="btn primary"><i class="bi bi-save"></i>บันทึกการตั้งค่า</button></div>
</form>
