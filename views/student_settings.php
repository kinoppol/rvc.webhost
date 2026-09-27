<?php
$forms = [
    ['ssh', 'pw_ssh', 'เปลี่ยนรหัสผ่าน SSH/SFTP', 'bi-key', 'รหัสผ่านเดิมจะใช้ไม่ได้ทันที ต้องแก้ไขรหัสผ่านใน FileZilla ด้วย', true],
    ['db', 'pw_db', 'เปลี่ยนรหัสผ่านฐานข้อมูล', 'bi-database-lock', 'ต้องแก้ไขรหัสผ่านในไฟล์ตั้งค่าการเชื่อมต่อฐานข้อมูลของเว็บไซต์ (เช่น config.php) ด้วย', true],
    ['portal', 'pw_portal', 'เปลี่ยนรหัสผ่านเข้าพอร์ทัล', 'bi-person-lock', 'ใช้รหัสผ่านใหม่ในการเข้าสู่ระบบครั้งถัดไป', false],
];
?>
<div class="grid-2" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,360px),1fr))">
  <div class="panel t-navy">
    <div class="panel-h"><h2>ข้อมูลบัญชี</h2></div>
    <div class="panel-b stack small" style="font-size:14px">
      <div style="display:flex;flex-direction:column;gap:6px"><span style="font-weight:500">Username</span>
        <div class="mono muted" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #dfe4ec;border-radius:6px;background:#f3f5f9"><i class="bi bi-lock-fill"></i><?= e($u['username']) ?></div>
        <span class="xs muted2">เปลี่ยนไม่ได้ เนื่องจากผูกกับบัญชี Linux ฐานข้อมูล และ Subdomain</span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div style="display:flex;flex-direction:column;gap:4px"><span class="muted small">ชื่อ-นามสกุล</span><span><?= e($u['full_name']) ?></span></div>
        <div style="display:flex;flex-direction:column;gap:4px"><span class="muted small">รหัสนักศึกษา</span><span class="mono"><?= e($u['student_id']) ?></span></div>
        <div style="display:flex;flex-direction:column;gap:4px;grid-column:1/-1"><span class="muted small">อีเมลสถาบัน</span><span><?= e($u['email']) ?></span></div>
        <div style="display:flex;flex-direction:column;gap:4px"><span class="muted small">รายวิชา</span><span><?= e($u['course'] ?: '-') ?></span></div>
        <div style="display:flex;flex-direction:column;gap:4px"><span class="muted small">วันที่สมัคร</span><span><?= e(thai_date($u['created_at'])) ?></span></div>
      </div>
    </div>
  </div>
  <div class="col">
    <?php foreach ($forms as [$id, $action, $ttl, $icon, $body, $server]): ?>
      <form method="post" id="<?= $id ?>" class="panel t-blue" data-pw-form data-confirm="<?= e($ttl) ?>" data-body="<?= e($body) ?>" data-icon="<?= $icon ?>" data-btn="เปลี่ยนรหัสผ่าน" <?= $server ? 'data-server="1"' : '' ?>>
        <?= csrf_field() ?><input type="hidden" name="action" value="<?= $action ?>">
        <div class="panel-h" style="justify-content:flex-start"><h2><i class="bi <?= $icon ?> c-blue"></i><?= e($ttl) ?></h2></div>
        <div class="panel-b stack" style="gap:12px">
          <?php if ($action === 'pw_portal'): ?><input type="password" name="current" placeholder="รหัสผ่านปัจจุบัน" autocomplete="current-password"><?php endif ?>
          <input type="password" name="password" placeholder="รหัสผ่านใหม่" autocomplete="new-password">
          <input type="password" name="password2" placeholder="ยืนยันรหัสผ่านใหม่" autocomplete="new-password">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
            <span class="small muted2 pw-hint">อย่างน้อย 8 ตัวอักษร</span>
            <button type="submit" class="btn primary" <?= ($server && $u['status'] !== 'active') ? 'disabled' : '' ?>>บันทึก</button>
          </div>
        </div>
      </form>
    <?php endforeach ?>
  </div>
</div>

<div class="panel t-red">
  <div class="panel-h"><h2 style="color:#b02a37">ปิด/ลบบัญชีของตนเอง</h2></div>
  <form method="post" class="panel-b" style="display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;align-items:center"
        data-confirm="ลบบัญชีของฉัน" data-kind="danger" data-server="1" data-type="<?= e($u['username']) ?>" data-btn="ลบบัญชีของฉัน"
        data-body="ไฟล์เว็บไซต์ ฐานข้อมูล และบัญชี SFTP ของ <?= e($u['username']) ?> จะถูกลบถาวร คุณจะต้องสมัครใหม่หากต้องการใช้งานอีก">
    <?= csrf_field() ?><input type="hidden" name="action" value="self_delete"><input type="hidden" name="confirm" value="<?= e($u['username']) ?>">
    <p class="muted" style="margin:0;font-size:14px;line-height:1.7;max-width:640px">ไฟล์เว็บไซต์ ฐานข้อมูล และบัญชี SFTP ทั้งหมดจะถูกลบออกจากเซิร์ฟเวอร์ถาวร และกู้คืนไม่ได้</p>
    <button class="btn danger-o">ลบบัญชีของฉัน</button>
  </form>
</div>
