<div class="stepper">
<?php foreach (['กรอกข้อมูล', 'ยืนยันอีเมล', 'รับข้อมูลเชื่อมต่อ'] as $i => $label): ?>
  <div class="<?= $i <= $stepIdx ? 'on' : '' ?>"><div class="bar"></div><span><?= $label ?></span></div>
<?php endforeach ?>
</div>
