<?php
$left = $rb['total'] - $rb['done'];
$pct = (int)round($rb['done'] / max(1, $rb['total']) * 100);
$onlyPending = ($_GET['only'] ?? '') === 'pending';
$counts = ['pass' => 0, 'fail' => 0, 'unknown' => 0, 'manual' => 0];
foreach ($rb['sections'] as $s) foreach ($s['items'] as $it) if (!$it['done']) $counts[$it['state']]++;
$icon = fn($it) => $it['done']
    ? ($it['state'] === 'pass' ? ['bi-check-circle-fill', 'c-ok', 'ตรวจพบอัตโนมัติ'] : ['bi-check-circle-fill', 'c-ok', 'ยืนยันโดยผู้ดูแล'])
    : match ($it['state']) {
        'fail' => ['bi-x-circle-fill', 'c-bad', 'ยังไม่เสร็จ'],
        'unknown' => ['bi-question-circle-fill', 'c-warn', 'ตรวจอัตโนมัติไม่ได้'],
        default => ['bi-circle', 'muted2', 'รอตรวจสอบด้วยตนเอง'],
    };
?>
<div class="panel <?= $left ? 't-warn' : 't-ok' ?>">
  <div class="panel-b stack" style="gap:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
      <div>
        <div style="font-size:28px;font-weight:700;color:#0a1f44"><?= $pct ?>% <span style="font-size:15px;font-weight:400" class="muted">(<?= $rb['done'] ?> / <?= $rb['total'] ?> รายการ)</span></div>
        <div class="small muted"><?= $left ? "เหลืออีก $left รายการที่ต้องดำเนินการเพื่อให้ระบบทำงานได้อย่างสมบูรณ์" : 'ดำเนินการครบทุกขั้นตอนตามคู่มือติดตั้งแล้ว' ?></div>
      </div>
      <div class="row">
        <a class="btn sm <?= $onlyPending ? '' : 'primary' ?>" href="<?= e(url('admin_runbook')) ?>">ทั้งหมด</a>
        <a class="btn sm <?= $onlyPending ? 'primary' : '' ?>" href="<?= e(url('admin_runbook', ['only' => 'pending'])) ?>">เฉพาะที่ยังค้าง</a>
        <a class="btn sm" href="<?= e(url('admin_runbook', $onlyPending ? ['only' => 'pending'] : [])) ?>"><i class="bi bi-arrow-clockwise"></i>ตรวจอีกครั้ง</a>
      </div>
    </div>
    <div class="bar thick"><div class="<?= $left ? 'warn' : 'ok' ?>" style="width:<?= $pct ?>%"></div></div>
    <?php if ($left): ?>
    <div class="row small">
      <?php if ($counts['fail']): ?><span class="pill over"><i class="bi bi-x-circle"></i> ตรวจพบว่ายังไม่เสร็จ <?= $counts['fail'] ?></span><?php endif ?>
      <?php if ($counts['unknown']): ?><span class="pill warn"><i class="bi bi-question-circle"></i> ตรวจอัตโนมัติไม่ได้ <?= $counts['unknown'] ?></span><?php endif ?>
      <?php if ($counts['manual']): ?><span class="pill pending"><i class="bi bi-person-check"></i> ต้องยืนยันด้วยตนเอง <?= $counts['manual'] ?></span><?php endif ?>
    </div>
    <?php endif ?>
    <?php if (PHP_OS_FAMILY !== 'Linux'): ?>
      <div class="alert info"><i class="bi bi-info-circle"></i><span>พอร์ทัลนี้กำลังทำงานบน <?= e(PHP_OS_FAMILY) ?> — การตรวจอัตโนมัติส่วนใหญ่ต้องรันบนเซิร์ฟเวอร์ TurnKey Linux จริง ระหว่างนี้ให้ติ๊กยืนยันด้วยตนเองหลังดำเนินการบนเซิร์ฟเวอร์</span></div>
    <?php endif ?>
    <div class="xs muted2">อ้างอิง: คู่มือดำเนินการติดตั้งระบบเว็บโฮสติ้งสำหรับนักศึกษา (installation-runbook.md) หัวข้อ 1–8 · รายการที่มีป้าย <span class="tag">พอร์ทัล</span> เป็นขั้นตอนเพิ่มเติมที่พอร์ทัลนี้ต้องใช้ · สคริปต์ตัวอย่างอยู่ในโฟลเดอร์ <code>server-scripts/</code></div>
  </div>
</div>

<div class="panel">
<?php foreach ($rb['sections'] as $s):
    $items = $onlyPending ? array_filter($s['items'], fn($it) => !$it['done']) : $s['items'];
    if (!$items) continue;
    $secDone = $s['done'] === count($s['items']); ?>
  <details class="rb-sec" <?= !$secDone || $onlyPending ? 'open' : '' ?>>
    <summary>
      <span class="no <?= $secDone ? 'done' : '' ?>"><?= $secDone ? '<i class="bi bi-check-lg"></i>' : $s['no'] ?></span>
      <strong class="grow"><?= e($s['title']) ?></strong>
      <span class="small muted"><?= $s['done'] ?>/<?= count($s['items']) ?></span>
      <i class="bi bi-chevron-right chev"></i>
    </summary>
    <?php foreach ($items as $it): [$ic, $cls, $lbl] = $icon($it); ?>
      <div class="rb-item" id="<?= e($it['key']) ?>">
        <i class="bi <?= $ic ?> <?= $cls ?> st" title="<?= e($lbl) ?>"></i>
        <div style="min-width:0">
          <div class="t"><span class="mono muted2 small"><?= e($it['key']) ?></span> <?= e($it['title']) ?><?php if (!empty($it['extra'])): ?><span class="tag">พอร์ทัล</span><?php endif ?></div>
          <div class="m"><span class="<?= $cls ?>"><?= e($lbl) ?></span> · <?= e($it['msg']) ?></div>
          <?php if (!empty($it['warn'])): ?><div class="alert warn" style="margin-top:8px;font-size:13px;padding:8px 12px"><i class="bi bi-exclamation-triangle"></i><span><?= e($it['warn']) ?></span></div><?php endif ?>
          <?php if (!empty($it['cmd']) && !$it['done']): ?><pre><?= e($it['cmd']) ?></pre><?php endif ?>
          <?php if ($it['ticked']): ?><div class="note"><i class="bi bi-person-check"></i> ติ๊กโดย <?= e($it['updated_by']) ?> เมื่อ <?= e(thai_date($it['updated_at'], true)) ?><?= $it['note'] !== '' ? ' — ' . e($it['note']) : '' ?></div><?php endif ?>
        </div>
        <?php if ($it['state'] !== 'pass'): ?>
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="key" value="<?= e($it['key']) ?>">
            <?php if ($it['ticked']): ?>
              <input type="hidden" name="done" value="0"><button class="btn sm"><i class="bi bi-arrow-counterclockwise"></i>ยกเลิกการติ๊ก</button>
            <?php else: ?>
              <input type="hidden" name="done" value="1">
              <input name="note" placeholder="หมายเหตุ (ถ้ามี)" maxlength="500">
              <button class="btn sm primary"><i class="bi bi-check2"></i>ทำแล้ว</button>
            <?php endif ?>
          </form>
        <?php endif ?>
      </div>
    <?php endforeach ?>
  </details>
<?php endforeach ?>
<?php if ($onlyPending && !$left): ?><div class="empty"><i class="bi bi-check-circle c-ok" style="font-size:28px"></i><br>ไม่มีรายการค้าง</div><?php endif ?>
</div>
