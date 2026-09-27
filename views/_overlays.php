<div class="toasts">
<?php foreach (take_flashes() as $f): ?>
  <div class="toast <?= e($f['type']) ?>" <?= $f['type'] === 'ok' ? 'data-autohide' : '' ?>>
    <i class="bi <?= $f['type'] === 'err' ? 'bi-x-circle-fill' : ($f['type'] === 'warn' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill') ?>"></i>
    <span class="grow"><?= e($f['msg']) ?></span>
    <button type="button" class="btn link" style="color:#9fb3d9" onclick="this.parentNode.remove()" aria-label="ปิด"><i class="bi bi-x" style="color:inherit;margin:0"></i></button>
  </div>
<?php endforeach ?>
</div>
<div id="confirm-modal" class="modal-back hidden">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-b"><span class="modal-ic"><i class="bi"></i></span><div><h3></h3><p></p></div></div>
    <label class="modal-type hidden"><span>พิมพ์ <strong class="mono"></strong> เพื่อยืนยัน</span><input class="mono" autocomplete="off"></label>
    <div class="modal-f">
      <span class="note"><i class="bi bi-hdd-rack"></i>คำสั่งนี้มีผลกับเซิร์ฟเวอร์จริง</span>
      <button type="button" class="btn" data-cancel>ยกเลิก</button>
      <button type="button" class="btn primary" data-ok>ยืนยัน</button>
    </div>
  </div>
</div>
<script src="<?= e(base_url('assets/app.js')) ?>"></script>
