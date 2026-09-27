<?php
$pending = array_filter($rows, fn($r) => !$r['applied']);
$lastBatch = max([0, ...array_filter(array_column($rows, 'batch'))]);
?>
<div class="grid-auto">
  <div class="stat"><span class="ic" style="background:#0a1f44"><i class="bi bi-database"></i></span><div><span class="small muted">ฐานข้อมูล</span><div class="v" style="font-size:16px"><?= e($dbVersion) ?></div><span class="xs muted2"><?= e(config('db.name')) ?></span></div></div>
  <div class="stat"><span class="ic" style="background:#146c43"><i class="bi bi-check2-all"></i></span><div><span class="small muted">รันแล้ว</span><div class="v"><?= count($rows) - count($pending) ?></div><span class="xs muted2">batch ล่าสุด #<?= $lastBatch ?></span></div></div>
  <div class="stat"><span class="ic" style="background:<?= $pending ? '#997404' : '#6c757d' ?>"><i class="bi bi-hourglass-split"></i></span><div><span class="small muted">รอดำเนินการ</span><div class="v"><?= count($pending) ?></div><span class="xs muted2"><?= $pending ? 'ควรรันเพื่ออัปเดตโครงสร้าง' : 'โครงสร้างเป็นปัจจุบัน' ?></span></div></div>
</div>

<div class="panel t-navy">
  <div class="panel-h">
    <h2>รายการ Migrations</h2>
    <div class="row">
      <form method="post" class="inline" data-confirm="รัน migrations ที่ค้างทั้งหมด" data-icon="bi-play-fill" data-btn="รันทั้งหมด"
            data-body="จะรัน <?= count($pending) ?> รายการตามลำดับในชุด (batch) ใหม่ · แนะนำให้สำรองฐานข้อมูลก่อน (mysqldump)">
        <?= csrf_field() ?><input type="hidden" name="action" value="migrate">
        <button class="btn primary sm" <?= $pending ? '' : 'disabled' ?>><i class="bi bi-play-fill"></i>รันที่ค้างทั้งหมด (<?= count($pending) ?>)</button>
      </form>
      <form method="post" class="inline" data-confirm="ย้อนกลับ batch ล่าสุด" data-kind="danger" data-icon="bi-arrow-counterclockwise" data-btn="ย้อนกลับ" data-type="rollback"
            data-body="จะรันคำสั่ง down ของทุก migration ใน batch #<?= $lastBatch ?> ซึ่งอาจลบตารางหรือคอลัมน์พร้อมข้อมูล">
        <?= csrf_field() ?><input type="hidden" name="action" value="rollback">
        <button class="btn sm danger-o" <?= $lastBatch ? '' : 'disabled' ?>><i class="bi bi-arrow-counterclockwise"></i>ย้อนกลับ batch ล่าสุด</button>
      </form>
    </div>
  </div>
  <div class="tbl-wrap">
    <table class="tbl" style="min-width:760px">
      <thead><tr><th>Migration</th><th>คำอธิบาย</th><th>Batch</th><th>รันเมื่อ</th><th>สถานะ</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="mono" style="font-size:12px"><?= e($r['name']) ?></td>
          <td class="small"><?= e($r['description']) ?></td>
          <td class="muted"><?= $r['batch'] ? '#' . (int)$r['batch'] : '-' ?></td>
          <td class="small muted"><?= $r['applied_at'] ? e(thai_date($r['applied_at'], true)) : '-' ?></td>
          <td><?php if ($r['missing']): ?><span class="pill over">ไม่พบไฟล์</span><?php elseif ($r['applied']): ?><span class="pill ok">รันแล้ว</span><?php else: ?><span class="pill warn">รอดำเนินการ</span><?php endif ?></td>
          <td style="white-space:nowrap;text-align:right">
            <?php if (!$r['applied']): ?>
              <form method="post" class="inline" data-confirm="รัน migration นี้" data-icon="bi-play" data-btn="รัน" data-body="<?= e($r['name']) ?>">
                <?= csrf_field() ?><input type="hidden" name="action" value="migrate"><input type="hidden" name="name" value="<?= e($r['name']) ?>">
                <button class="btn sm"><i class="bi bi-play"></i>รัน</button></form>
            <?php elseif ($r['has_down']): ?>
              <form method="post" class="inline" data-confirm="ย้อนกลับ migration นี้" data-kind="danger" data-icon="bi-arrow-counterclockwise" data-btn="ย้อนกลับ" data-type="<?= e($r['name']) ?>"
                    data-body="จะรันคำสั่ง down ซึ่งอาจลบตารางหรือคอลัมน์พร้อมข้อมูล">
                <?= csrf_field() ?><input type="hidden" name="action" value="rollback"><input type="hidden" name="name" value="<?= e($r['name']) ?>">
                <button class="btn sm"><i class="bi bi-arrow-counterclockwise"></i>ย้อนกลับ</button></form>
            <?php endif ?>
          </td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php if (!$rows): ?><div class="empty">ไม่พบไฟล์ในโฟลเดอร์ migrations/</div><?php endif ?>
  </div>
</div>

<div class="grid-2">
  <form method="post" class="panel t-blue">
    <?= csrf_field() ?><input type="hidden" name="action" value="create">
    <div class="panel-h"><h2><i class="bi bi-file-earmark-plus c-blue"></i>สร้างไฟล์ migration ใหม่</h2></div>
    <div class="panel-b stack" style="gap:12px">
      <label>คำอธิบาย (ภาษาอังกฤษจะใช้เป็นชื่อไฟล์)<input name="description" placeholder="เช่น add phone to users" <?= $writable ? '' : 'disabled' ?>></label>
      <?php if (!$writable): ?><div class="alert warn"><i class="bi bi-lock"></i><span>โฟลเดอร์ migrations/ เขียนไม่ได้ — สร้างไฟล์ผ่าน SFTP/SSH แทน</span></div><?php endif ?>
      <div class="row-end"><button class="btn primary" <?= $writable ? '' : 'disabled' ?>>สร้างไฟล์</button></div>
    </div>
  </form>
  <div class="panel">
    <div class="panel-h"><h2><i class="bi bi-info-circle c-blue"></i>รูปแบบไฟล์ migration</h2></div>
    <div class="panel-b small stack" style="gap:10px">
      <span class="muted">ไฟล์ <code>migrations/YYYY_MM_DD_HHMMSS_name.php</code> คืนค่า array ที่มี <code>up</code> และ <code>down</code> เป็นรายการคำสั่ง SQL หรือ <code>function (PDO $db)</code> — MariaDB ทำ DDL แบบ auto-commit จึงควรเขียนให้รันซ้ำได้ (IF NOT EXISTS / IF EXISTS)</span>
      <pre class="term" style="margin:0">&lt;?php
return [
  'description' =&gt; 'add phone to users',
  'up'   =&gt; ['ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL'],
  'down' =&gt; ['ALTER TABLE users DROP COLUMN IF EXISTS phone'],
];</pre>
    </div>
  </div>
</div>
