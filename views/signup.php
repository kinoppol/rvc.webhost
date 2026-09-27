<?php $stepIdx = 0; require __DIR__ . '/_stepper.php'; $o = fn($k) => e($old[$k] ?? ''); ?>
<div class="card">
  <div class="card-h"><h1>สมัครใช้งานเว็บโฮสติ้ง</h1><p>กรอกข้อมูลให้ตรงกับทะเบียนนักศึกษา</p></div>
  <?php if (!$seats['open']): ?>
    <div class="card-b center stack" style="padding:40px 24px">
      <i class="bi bi-hdd-stack" style="font-size:40px;color:#dc3545"></i>
      <h2 style="margin:0;font-size:20px;color:#1b2433">เต็มโควตา ไม่รับสมัครเพิ่ม</h2>
      <p class="muted" style="margin:0;line-height:1.7;max-width:380px">
        <?= $seats['used'] >= $seats['max'] ? 'ขณะนี้มีผู้ใช้ครบ ' . (int)$seats['max'] . ' บัญชีตามขีดจำกัดของเซิร์ฟเวอร์' : 'ผู้ดูแลระบบปิดรับสมัครชั่วคราว' ?>
        กรุณาติดต่อเจ้าหน้าที่ศูนย์คอมพิวเตอร์ หรือกลับมาใหม่เมื่อเปิดรับรอบถัดไป</p>
      <a class="btn" href="<?= e(url('landing')) ?>">กลับหน้าแรก</a>
    </div>
  <?php else: ?>
  <form method="post" id="signup-form" data-check="<?= e(url('api_username')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="card-b stack">
      <?php if ($errors): ?><div class="alert danger"><i class="bi bi-x-circle"></i><span><?= implode('<br>', array_map('e', $errors)) ?></span></div><?php endif ?>
      <label>ชื่อ-นามสกุล<input name="full_name" value="<?= $o('full_name') ?>" placeholder="เช่น สมชาย ใจดี" required></label>
      <div class="grid2">
        <label>รหัสนักศึกษา<input name="student_id" value="<?= $o('student_id') ?>" placeholder="เช่น 6621010001" inputmode="numeric" class="mono" required></label>
        <label>อีเมลสถาบัน<input name="email" type="email" value="<?= $o('email') ?>" placeholder="name@<?= e(Settings::get('email_domain') ?: 'college.ac.th') ?>" required></label>
      </div>
      <?php if ($courses): ?>
      <label>รายวิชา<select name="course"><option value="">— ไม่ระบุ —</option>
        <?php foreach ($courses as $c): ?><option <?= ($old['course'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach ?>
      </select></label>
      <?php endif ?>
      <label>Username ที่ต้องการ
        <div class="input-group"><input name="username" value="<?= $o('username') ?>" placeholder="เช่น somchai01" maxlength="16" class="mono" autocomplete="off" required><span class="suffix">.<?= e(Settings::get('domain')) ?></span></div>
        <span class="hint" id="u-msg"><i class="bi bi-info-circle"></i><span>ใช้ได้เฉพาะ a-z และ 0-9 ความยาว 4–16 ตัวอักษร</span></span>
      </label>
      <div class="alert info" style="font-size:13px"><i class="bi bi-lock"></i><span>username จะใช้เป็นชื่อบัญชี SFTP ฐานข้อมูล และ URL เว็บไซต์ และเปลี่ยนไม่ได้หลังยืนยันตัวตน</span></div>
      <div class="grid2">
        <label>รหัสผ่านเข้าพอร์ทัล<input name="password" type="password" minlength="8" autocomplete="new-password" required></label>
        <label>ยืนยันรหัสผ่าน<input name="password2" type="password" minlength="8" autocomplete="new-password" required></label>
      </div>
      <label class="check"><input type="checkbox" name="agree" id="agree" value="1" <?= !empty($_POST['agree']) ? 'checked' : '' ?>><span>ฉันได้อ่านและยอมรับ <a href="#" data-open-terms>ข้อตกลงการใช้งาน (Terms)</a></span></label>
    </div>
    <div class="card-f" style="justify-content:space-between">
      <a href="<?= e(url('login')) ?>" class="small">มีบัญชีแล้ว? เข้าสู่ระบบ</a>
      <button type="submit" class="btn primary" style="font-size:15px;padding:10px 22px">ส่งรหัสยืนยัน</button>
    </div>
  </form>
  <?php endif ?>
</div>

<div id="terms-modal" class="modal-back hidden">
  <div class="modal wide">
    <div style="padding:16px 20px;border-bottom:1px solid #dfe4ec"><h3 style="margin:0;font-size:18px">ข้อตกลงการใช้งาน (Terms)</h3></div>
    <ol style="margin:0;padding:16px 20px 16px 40px;display:flex;flex-direction:column;gap:10px;font-size:14px;line-height:1.7">
      <li>ใช้พื้นที่เพื่อการเรียนการสอนและโครงงานของนักศึกษาเท่านั้น</li>
      <li>ห้ามเผยแพร่เนื้อหาที่ผิดกฎหมาย ละเมิดลิขสิทธิ์ หรือโปรแกรมที่เป็นอันตราย</li>
      <li>ห้ามเปิดเผยรหัสผ่าน SFTP และฐานข้อมูลแก่ผู้อื่น</li>
      <li>บัญชีที่ไม่มีการใช้งานเกิน 1 ภาคเรียนอาจถูกระงับหรือลบโดยผู้ดูแลระบบ</li>
    </ol>
    <div style="padding:14px 20px;border-top:1px solid #dfe4ec;display:flex;justify-content:flex-end;gap:10px">
      <button type="button" class="btn" data-close-terms>ปิด</button>
      <button type="button" class="btn primary" data-accept-terms>ยอมรับ</button>
    </div>
  </div>
</div>
