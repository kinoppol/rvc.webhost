<?php require __DIR__ . '/_head.php'; ?>
<body class="public">
<header class="pub-head"><div class="pub-head-in">
  <a href="<?= e(url('landing')) ?>"><img src="<?= e(base_url('assets/vec_logo.png')) ?>" alt="โลโก้ สอศ." class="logo-38">Student Hosting Portal</a>
  <span class="grow"></span>
  <a class="back" href="<?= e(url('landing')) ?>"><i class="bi bi-arrow-left"></i>กลับหน้าแรก</a>
</div></header>
<main class="pub-main"><div class="pub-col <?= e($colClass ?? '') ?>">
<?= $content ?>
</div></main>
<?php require __DIR__ . '/_overlays.php'; ?>
</body>
</html>
