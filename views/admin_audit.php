<div class="panel t-navy">
  <div class="tbl-wrap">
    <table class="tbl" style="min-width:760px">
      <thead><tr><th>เวลา</th><th>ผู้ดำเนินการ</th><th>การกระทำ</th><th>เป้าหมาย</th><th>รายละเอียด</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($log as $l): ?>
        <tr>
          <td class="small muted" style="white-space:nowrap"><?= e(thai_date($l['created_at'], true)) ?></td>
          <td class="mono small"><?= e($l['actor']) ?></td>
          <td class="mono small"><?= e($l['action']) ?></td>
          <td class="mono small"><?= e($l['target']) ?></td>
          <td class="small muted" style="max-width:360px;overflow-wrap:anywhere"><?= e($l['detail']) ?></td>
          <td class="mono xs muted2"><?= e($l['ip']) ?></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php if (!$log): ?><div class="empty">ยังไม่มีบันทึก</div><?php endif ?>
  </div>
  <div class="panel-f"><span>แสดง 300 รายการล่าสุด</span></div>
</div>
