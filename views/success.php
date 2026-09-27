<?php $stepIdx = 2; require __DIR__ . '/_stepper.php'; ?>
<div class="card">
  <div style="padding:28px 24px;display:flex;gap:16px;align-items:center;border-bottom:1px solid #dfe4ec">
    <div class="circle ok" style="width:52px;height:52px;margin:0;flex:none;font-size:26px"><i class="bi bi-check-lg"></i></div>
    <div><h1 style="margin:0;font-size:22px">สมัครสำเร็จ</h1><p class="muted" style="margin:4px 0 0;font-size:14px">บัญชีของคุณพร้อมใช้งานแล้ว เก็บข้อมูลด้านล่างไว้ให้ดี</p></div>
  </div>
  <div style="padding:8px 24px 16px">
    <?php foreach ($rows as [$k, $v]): ?>
      <div class="conn-row" data-conn-row data-label="<?= e($k) ?>" data-value="<?= e($v) ?>">
        <span class="k"><?= e($k) ?></span><span class="v"><?= e($v) ?></span>
        <button type="button" class="icon-btn no-print" data-copy="<?= e($v) ?>" title="คัดลอก"><i class="bi bi-clipboard"></i></button>
      </div>
    <?php endforeach ?>
  </div>
  <div class="alert warn" style="margin:0 24px 20px;font-size:13px"><i class="bi bi-exclamation-triangle"></i><span>รหัสผ่านจะแสดงเพียงครั้งเดียว กรุณาดาวน์โหลดหรือคัดลอกเก็บไว้ก่อนออกจากหน้านี้</span></div>
  <div class="card-f">
    <button type="button" class="btn" data-download-txt="<?= e($u['username']) ?>-hosting.txt"><i class="bi bi-filetype-txt"></i>ดาวน์โหลด Text</button>
    <button type="button" class="btn" data-print><i class="bi bi-filetype-pdf"></i>บันทึกเป็น PDF</button>
    <span class="grow"></span>
    <a class="btn primary" href="<?= e(url('dash')) ?>">ไปที่แดชบอร์ด</a>
  </div>
</div>
