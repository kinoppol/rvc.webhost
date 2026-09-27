<?php
$key = user_status_key($u); $st = status_cfg($key); $pct = usage_pct($u); $susp = $u['status'] === 'suspended';
$hidden = csrf_field() . '<input type="hidden" name="username" value="' . e($u['username']) . '"><input type="hidden" name="back" value="user">';
?>
<div><a href="<?= e(url('admin_users')) ?>" class="small"><i class="bi bi-arrow-left"></i> กลับไปรายชื่อผู้ใช้</a></div>

<?php if ($creds): ?>
<div class="panel t-ok">
  <div class="panel-h"><h2><i class="bi bi-key c-ok"></i>ข้อมูลเชื่อมต่อของ <?= e($u['username']) ?> (แสดงครั้งเดียว)</h2>
    <button type="button" class="btn sm" data-download-txt="<?= e($u['username']) ?>-hosting.txt"><i class="bi bi-filetype-txt"></i>ดาวน์โหลด</button></div>
  <div class="panel-l">
    <?php foreach ($creds['rows'] as [$k, $v]): ?>
      <div class="conn-row" data-conn-row data-label="<?= e($k) ?>" data-value="<?= e($v) ?>"><span class="k"><?= e($k) ?></span><span class="v"><?= e($v) ?></span>
        <button type="button" class="icon-btn" data-copy="<?= e($v) ?>"><i class="bi bi-clipboard"></i></button></div>
    <?php endforeach ?>
  </div>
</div>
<?php endif ?>

<div class="panel">
  <div class="panel-h" style="justify-content:flex-start;gap:12px"><h2 class="mono" style="font-size:18px"><?= e($u['username']) ?></h2><span class="pill <?= $st['cls'] ?>"><?= $st['label'] ?></span></div>
  <div class="panel-b" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;font-size:14px;border-bottom:1px solid #eef1f6">
    <?php foreach ([['ชื่อ-นามสกุล', $u['full_name'], ''], ['รหัสนักศึกษา', $u['student_id'], 'mono'], ['อีเมล', $u['email'], ''],
                    ['รายวิชา / ปีการศึกษา', ($u['course'] ?: '-') . ' · ' . ($u['academic_year'] ?: '-'), ''], ['วันที่สมัคร', thai_date($u['created_at'], true), ''],
                    ['เข้าสู่ระบบล่าสุด', thai_date($u['last_login_at'], true), ''], ['Base URL', site_url($u['username']), 'mono'],
                    ['สร้างบนเซิร์ฟเวอร์', $u['provisioned_at'] ? thai_date($u['provisioned_at'], true) : 'ยังไม่ได้สร้าง', '']] as [$l, $v, $cls]): ?>
      <div style="display:flex;flex-direction:column;gap:3px"><span class="xs muted"><?= e($l) ?></span><span class="<?= $cls ?>" style="overflow-wrap:anywhere"><?= e($v) ?></span></div>
    <?php endforeach ?>
  </div>
  <form method="post" class="panel-b stack" style="gap:12px;border-bottom:1px solid #eef1f6" data-quota-form data-user="<?= e($u['username']) ?>"
        data-confirm="ยืนยันการปรับโควตา" data-icon="bi-sliders" data-btn="ยืนยัน" data-server="1">
    <?= $hidden ?><input type="hidden" name="action" value="quota">
    <div style="display:flex;justify-content:space-between;font-size:14px"><strong>การใช้พื้นที่</strong><span class="muted"><?= e($u['disk_used_mb']) ?> / <?= (int)$u['quota_soft_mb'] ?> MB (<?= $pct ?>%)</span></div>
    <div class="bar"><div class="<?= in_array($key, ['ok', 'warn', 'pending'], true) ? $key : 'over' ?>" style="width:<?= min(100, $pct) ?>%"></div></div>
    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;margin-top:6px">
      <label class="small">Soft limit (MB)<input type="number" name="soft" value="<?= (int)$u['quota_soft_mb'] ?>" min="1" max="5000"></label>
      <label class="small">Hard limit (MB)<input type="number" name="hard" value="<?= (int)$u['quota_hard_mb'] ?>" min="1" max="5000"></label>
      <button class="btn primary" style="padding:10px 16px">ปรับโควตา</button>
    </div>
    <span class="xs muted2 q-hint" data-def="ค่าเริ่มต้น: Soft <?= Settings::int('default_soft_mb') ?> MB / Hard <?= Settings::int('default_hard_mb') ?> MB"></span>
  </form>
  <div class="panel-b" style="display:flex;gap:10px;flex-wrap:wrap;background:#fafbfd;border-radius:0 0 8px 8px">
    <?php if ($u['status'] === 'pending'): ?>
      <form method="post" class="inline" data-confirm="อนุมัติและสร้างบัญชี" data-server="1" data-icon="bi-person-check" data-btn="อนุมัติ"
            data-body="ข้ามการยืนยันอีเมลและสร้างบัญชี Linux ฐานข้อมูล และโควตาของ <?= e($u['username']) ?> บนเซิร์ฟเวอร์ทันที">
        <?= $hidden ?><input type="hidden" name="action" value="approve"><button class="btn primary"><i class="bi bi-person-check"></i>อนุมัติและสร้างบัญชี</button></form>
    <?php else: ?>
      <form method="post" class="inline" data-confirm="<?= $susp ? 'ยกเลิกการระงับ' : 'ระงับบัญชีชั่วคราว' ?>" data-kind="<?= $susp ? 'info' : 'warn' ?>" data-server="1"
            data-icon="<?= $susp ? 'bi-play-circle' : 'bi-pause-circle' ?>" data-btn="<?= $susp ? 'ยกเลิกการระงับ' : 'ระงับบัญชี' ?>"
            data-body="<?= $susp ? 'เปิดใช้งานบัญชีอีกครั้ง ผู้ใช้จะเข้าใช้ SFTP และเว็บไซต์ได้ตามปกติ' : 'ผู้ใช้จะเข้าใช้ SFTP และฐานข้อมูลไม่ได้ และเว็บไซต์จะไม่แสดงผล จนกว่าจะยกเลิกการระงับ' ?>">
        <?= $hidden ?><input type="hidden" name="action" value="<?= $susp ? 'unsuspend' : 'suspend' ?>">
        <button class="btn warn-o"><i class="bi <?= $susp ? 'bi-play-circle' : 'bi-pause-circle' ?>"></i><?= $susp ? 'ยกเลิกการระงับ' : 'ระงับชั่วคราว' ?></button></form>
    <?php endif ?>
    <span class="grow"></span>
    <form method="post" class="inline" data-confirm="ลบบัญชีถาวร" data-kind="danger" data-server="1" data-type="<?= e($u['username']) ?>" data-btn="ลบถาวร" data-icon="bi-trash3"
          data-body="ระบบจะลบบัญชี Linux ฐานข้อมูล MariaDB ผู้ใช้ฐานข้อมูล และไฟล์เว็บไซต์ของ <?= e($u['username']) ?> ออกจากเซิร์ฟเวอร์ และกู้คืนไม่ได้">
      <?= $hidden ?><input type="hidden" name="action" value="delete"><input type="hidden" name="confirm" value="<?= e($u['username']) ?>">
      <button class="btn danger"><i class="bi bi-trash3"></i>ลบบัญชีถาวร</button></form>
  </div>
</div>

<div class="panel">
  <div class="panel-h"><h2>ประวัติการดำเนินการ</h2></div>
  <div class="panel-l">
    <?php if (!$log): ?><div class="empty">ยังไม่มีประวัติ</div><?php endif ?>
    <?php foreach ($log as $l): ?>
      <div class="list-row small"><span class="muted" style="width:170px"><?= e(thai_date($l['created_at'], true)) ?></span><span class="mono"><?= e($l['action']) ?></span><span class="muted">โดย <?= e($l['actor']) ?></span><span class="muted2 grow"><?= e($l['detail']) ?></span></div>
    <?php endforeach ?>
  </div>
</div>
