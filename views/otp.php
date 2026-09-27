<?php $stepIdx = 1; require __DIR__ . '/_stepper.php'; ?>
<div class="card"><div class="card-b center stack" style="padding:36px 28px">
  <div class="circle info"><i class="bi bi-envelope-check"></i></div>
  <h1 style="margin:0;font-size:22px">ยืนยันอีเมลสถาบัน</h1>
  <p class="muted" style="margin:0;line-height:1.7">กรอกรหัส 6 หลักที่ส่งไปยัง<br><strong style="color:#1b2433"><?= e($u['email']) ?></strong></p>
  <form method="post" class="stack center" style="gap:14px">
    <?= csrf_field() ?>
    <input name="otp" class="otp-input" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" autofocus>
    <?php if ($error): ?><span class="c-bad small"><i class="bi bi-x-circle"></i> <?= e($error) ?></span><?php endif ?>
    <button id="otp-submit" class="btn primary" style="width:260px;max-width:100%;padding:12px;font-size:15px">ยืนยันและสร้างบัญชี</button>
  </form>
  <div class="small muted" style="display:flex;gap:6px;align-items:center">ไม่ได้รับรหัส?
    <span id="resend-wait" data-countdown="<?= (int)$wait ?>" class="<?= $wait ? '' : 'hidden' ?>">ส่งใหม่ได้ใน <span><?= (int)$wait ?></span> วินาที</span>
    <form method="post" id="resend-form" class="inline <?= $wait ? 'hidden' : '' ?>"><?= csrf_field() ?><input type="hidden" name="action" value="resend"><button class="btn link" style="font-weight:600;font-size:14px">ส่งรหัสใหม่</button></form>
  </div>
  <span class="xs muted2">รหัสมีอายุ 10 นาที · username: <span class="mono"><?= e($u['username']) ?></span></span>
</div></div>
