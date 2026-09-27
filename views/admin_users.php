<?php
$courses = array_filter(array_map('trim', explode(',', Settings::get('courses'))));
$years = array_values(array_unique(array_filter(array_column($users, 'academic_year'))));
rsort($years);
$rows = array_map(fn($u) => $u + ['key' => user_status_key($u), 'pct' => usage_pct($u)], $users);
$q = mb_strtolower($f['q']);
$rows = array_filter($rows, fn($u) =>
    ($q === '' || str_contains($u['username'], $q) || str_contains(mb_strtolower($u['full_name']), $q) || str_contains((string)$u['student_id'], $q)) &&
    ($f['course'] === '' || $u['course'] === $f['course']) &&
    ($f['year'] === '' || $u['academic_year'] === $f['year']) &&
    ($f['status'] === '' || $u['key'] === $f['status']));
$order = ['over' => 0, 'warn' => 1, 'suspended' => 2, 'pending' => 3, 'ok' => 4];
$getK = fn($u) => match ($f['sort']) { 'status' => $order[$u['key']], 'used' => $u['pct'], 'username' => $u['username'], 'full_name' => $u['full_name'], 'year' => $u['academic_year'], default => $u['created_at'] };
usort($rows, fn($a, $b) => $f['dir'] === 'asc' ? $getK($a) <=> $getK($b) : $getK($b) <=> $getK($a));
$qs = http_build_query(array_filter($f, fn($v) => $v !== ''));
$sortLink = function (string $k, string $label) use ($f) {
    $on = $f['sort'] === $k;
    $dir = $on && $f['dir'] === 'asc' ? 'desc' : 'asc';
    $icon = $on ? ($f['dir'] === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill') : 'bi-chevron-expand';
    return '<a class="' . ($on ? 'on' : '') . '" href="' . e(url('admin_users', array_merge($f, ['sort' => $k, 'dir' => $dir]))) . '">' . $label . '<i class="bi ' . $icon . '"></i></a>';
};
?>
<div class="panel t-navy">
  <form method="get" class="filters">
    <input type="hidden" name="p" value="admin_users">
    <input type="hidden" name="sort" value="<?= e($f['sort']) ?>"><input type="hidden" name="dir" value="<?= e($f['dir']) ?>">
    <div class="search"><i class="bi bi-search muted2"></i><input name="q" value="<?= e($f['q']) ?>" placeholder="ค้นหา username, ชื่อ, รหัสนักศึกษา"></div>
    <select name="course" data-autosubmit><option value="">ทุกรายวิชา</option>
      <?php foreach ($courses as $c): ?><option <?= $f['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach ?></select>
    <select name="year" data-autosubmit><option value="">ทุกปีการศึกษา</option>
      <?php foreach ($years as $y): ?><option <?= $f['year'] === $y ? 'selected' : '' ?>><?= e($y) ?></option><?php endforeach ?></select>
    <select name="status" data-autosubmit><option value="">ทุกสถานะ</option>
      <?php foreach (['ok', 'warn', 'over', 'suspended', 'pending'] as $s): ?><option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= status_cfg($s)['label'] ?></option><?php endforeach ?></select>
    <button class="btn"><i class="bi bi-funnel"></i>กรอง</button>
  </form>
  <div class="tbl-wrap">
    <table class="tbl" style="min-width:1060px">
      <thead><tr>
        <th><?= $sortLink('username', 'Username') ?></th><th><?= $sortLink('full_name', 'ชื่อ-นามสกุล') ?></th><th>รายวิชา</th>
        <th><?= $sortLink('year', 'ปี') ?></th><th><?= $sortLink('used', 'การใช้พื้นที่') ?></th><th><?= $sortLink('created', 'วันที่สมัคร') ?></th>
        <th><?= $sortLink('status', 'สถานะ') ?></th><th>จัดการ</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $u): $st = status_cfg($u['key']); $susp = $u['status'] === 'suspended'; ?>
        <tr>
          <td class="mono" style="font-size:13px"><?= e($u['username']) ?></td>
          <td><div><?= e($u['full_name']) ?></div><div class="sub"><?= e($u['student_id']) ?></div></td>
          <td class="small muted"><?= e($u['course'] ?: '-') ?></td>
          <td class="muted"><?= e($u['academic_year'] ?: '-') ?></td>
          <td style="min-width:170px"><div class="xs muted" style="margin-bottom:5px"><?= e($u['disk_used_mb']) ?> / <?= (int)$u['quota_soft_mb'] ?> MB</div>
            <div class="bar thin"><div class="<?= in_array($u['key'], ['ok', 'warn', 'pending'], true) ? $u['key'] : 'over' ?>" style="width:<?= min(100, $u['pct']) ?>%"></div></div></td>
          <td class="small muted"><?= e(thai_date($u['created_at'])) ?></td>
          <td><span class="pill <?= $st['cls'] ?>"><?= $st['label'] ?></span></td>
          <td style="white-space:nowrap">
            <a class="icon-btn" href="<?= e(url('admin_user', ['u' => $u['username']])) ?>" title="จัดการ / ปรับโควตา"><i class="bi bi-sliders"></i></a>
            <?php if ($u['status'] !== 'pending'): ?>
            <form method="post" class="inline" data-confirm="<?= $susp ? 'ยกเลิกการระงับ' : 'ระงับบัญชีชั่วคราว' ?>" data-kind="<?= $susp ? 'info' : 'warn' ?>" data-server="1"
                  data-icon="<?= $susp ? 'bi-play-circle' : 'bi-pause-circle' ?>" data-btn="<?= $susp ? 'ยกเลิกการระงับ' : 'ระงับบัญชี' ?>"
                  data-body="<?= $susp ? 'เปิดใช้งานบัญชี ' . e($u['username']) . ' อีกครั้ง ผู้ใช้จะเข้าใช้ SFTP และเว็บไซต์ได้ตามปกติ' : 'บัญชี ' . e($u['username']) . ' จะเข้าใช้ SFTP และฐานข้อมูลไม่ได้ และเว็บไซต์จะไม่แสดงผล จนกว่าจะยกเลิกการระงับ' ?>">
              <?= csrf_field() ?><input type="hidden" name="username" value="<?= e($u['username']) ?>"><input type="hidden" name="qs" value="<?= e($qs) ?>">
              <input type="hidden" name="action" value="<?= $susp ? 'unsuspend' : 'suspend' ?>">
              <button class="icon-btn warn" title="<?= $susp ? 'ยกเลิกการระงับ' : 'ระงับชั่วคราว' ?>"><i class="bi <?= $susp ? 'bi-play-circle' : 'bi-pause-circle' ?>"></i></button>
            </form>
            <?php endif ?>
            <form method="post" class="inline" data-confirm="ลบบัญชีถาวร" data-kind="danger" data-server="1" data-type="<?= e($u['username']) ?>" data-btn="ลบถาวร" data-icon="bi-trash3"
                  data-body="ระบบจะลบบัญชี Linux ฐานข้อมูล MariaDB ผู้ใช้ฐานข้อมูล และไฟล์เว็บไซต์ของ <?= e($u['username']) ?> ออกจากเซิร์ฟเวอร์ และกู้คืนไม่ได้">
              <?= csrf_field() ?><input type="hidden" name="username" value="<?= e($u['username']) ?>"><input type="hidden" name="qs" value="<?= e($qs) ?>">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="confirm" value="<?= e($u['username']) ?>">
              <button class="icon-btn bad" title="ลบบัญชีถาวร"><i class="bi bi-trash3"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php if (!$rows): ?><div class="empty">ไม่พบผู้ใช้ตามเงื่อนไขที่เลือก</div><?php endif ?>
  </div>
  <div class="panel-f">
    <span>แสดง <?= count($rows) ?> รายการ</span>
    <span><i class="bi bi-clock-history"></i> การใช้พื้นที่อัปเดตล่าสุดเมื่อ <?= Settings::get('last_usage_sync') ? e(thai_date(Settings::get('last_usage_sync'), true)) : '—' ?></span>
  </div>
</div>
