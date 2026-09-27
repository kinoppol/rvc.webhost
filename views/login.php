<?php $isStu = $role === 'student'; $tab = 'padding:8px;border-radius:6px;text-align:center;font-size:14px;font-weight:600;text-decoration:none;'; ?>
<div class="card" style="max-width:420px;width:100%;margin:0 auto">
  <div style="padding:28px 28px 4px;display:flex;flex-direction:column;align-items:center;gap:12px">
    <img src="<?= e(base_url('assets/vec_logo.png')) ?>" alt="โลโก้ สอศ." style="width:72px;height:72px">
    <h1 style="margin:0;font-size:22px">เข้าสู่ระบบ</h1>
  </div>
  <div style="margin:16px 28px 0;display:grid;grid-template-columns:1fr 1fr;background:#f3f5f9;border-radius:8px;padding:4px">
    <a href="<?= e(url('login')) ?>" style="<?= $tab ?><?= $isStu ? 'background:#fff;color:#0a1f44' : 'color:#5b6577' ?>">นักศึกษา</a>
    <a href="<?= e(url('login', ['role' => 'admin'])) ?>" style="<?= $tab ?><?= !$isStu ? 'background:#fff;color:#0a1f44' : 'color:#5b6577' ?>">ผู้ดูแลระบบ</a>
  </div>
  <form method="post" style="padding:20px 28px;display:flex;flex-direction:column;gap:14px">
    <?= csrf_field() ?><input type="hidden" name="role" value="<?= e($role) ?>">
    <?php if ($error): ?><div class="alert danger"><i class="bi bi-x-circle"></i><span><?= e($error) ?></span></div><?php endif ?>
    <label>Username<input name="username" value="<?= e(post('username')) ?>" class="mono" autocomplete="username" required autofocus></label>
    <label>รหัสผ่าน<input name="password" type="password" autocomplete="current-password" required></label>
    <button class="btn primary" style="padding:12px;font-size:15px">เข้าสู่ระบบ</button>
  </form>
  <?php if ($isStu): ?>
  <div style="padding:14px 28px;border-top:1px solid #dfe4ec;text-align:center">
    <a href="<?= e(url('signup')) ?>" class="small">ยังไม่มีบัญชี? สมัครใช้งาน</a>
  </div>
  <?php endif ?>
</div>
