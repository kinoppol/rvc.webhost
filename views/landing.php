<?php $domain = Settings::get('domain'); $logo = e(base_url('assets/vec_logo.png')); ?>
<header class="land-head"><div class="land-head-in">
  <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:220px">
    <img src="<?= $logo ?>" alt="โลโก้ สอศ." style="width:44px;height:44px;flex:none">
    <div style="display:flex;flex-direction:column;line-height:1.25">
      <span style="color:#fff;font-weight:600;font-size:16px">Student Hosting Portal</span>
      <span style="color:#9fb3d9;font-size:12px">บริการเว็บโฮสติ้งสำหรับนักศึกษา</span>
    </div>
  </div>
  <nav><a href="#features">ฟังก์ชัน</a><a href="#steps">ขั้นตอน</a><a href="#specs">สิ่งที่ได้รับ</a></nav>
  <div style="display:flex;gap:8px">
    <?php if ($me): ?>
      <a class="btn white" href="<?= e(url($me['role'] === 'admin' ? 'admin' : 'dash')) ?>">ไปที่แดชบอร์ด</a>
    <?php else: ?>
      <a class="btn ghost" href="<?= e(url('login')) ?>">เข้าสู่ระบบ</a>
      <a class="btn white" href="<?= e(url('signup')) ?>">สมัครใช้งาน</a>
    <?php endif ?>
  </div>
</div></header>

<section class="hero"><div class="hero-in">
  <div style="display:flex;flex-direction:column;gap:24px">
    <?php if ($seats['open']): ?>
      <div class="badge-open"><span class="dot" style="background:#3ddc84"></span>เปิดรับสมัคร · เหลือที่ว่าง <?= (int)$seats['left'] ?> บัญชี</div>
    <?php else: ?>
      <div class="badge-full"><i class="bi bi-exclamation-octagon"></i>ขณะนี้ระบบเต็มโควตาหรือปิดรับสมัครชั่วคราว</div>
    <?php endif ?>
    <h1>พื้นที่เว็บไซต์ของคุณเอง พร้อมใช้ภายในไม่กี่นาที</h1>
    <p>สมัครด้วยรหัสนักศึกษาและอีเมลสถาบัน ระบบจะสร้างบัญชี SFTP ฐานข้อมูล MariaDB และ URL เว็บไซต์ให้อัตโนมัติ บนเซิร์ฟเวอร์ LAMP ของสถาบัน</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <a class="btn white lg" href="<?= e(url('signup')) ?>">สมัครใช้งาน<i class="bi bi-arrow-right"></i></a>
      <a class="btn ghost lg" href="<?= e(url('login')) ?>">เข้าสู่ระบบ</a>
    </div>
    <div class="mono" style="font-size:14px;color:#9fb3d9">http://<span style="color:#fff">username</span>.<?= e($domain) ?></div>
  </div>
  <div class="mock">
    <div class="mock-bar"><span class="d"></span><span class="d"></span><span class="d"></span><span style="margin-left:10px;font-size:12px;color:#5b6577">แดชบอร์ดนักศึกษา</span></div>
    <div style="padding:22px;display:flex;flex-direction:column;gap:16px">
      <div style="display:flex;justify-content:space-between;align-items:baseline"><span style="font-weight:600">โควตา (Quota)</span><span class="small muted">312 / <?= Settings::int('default_soft_mb') ?> MB</span></div>
      <div class="bar"><div style="width:62%"></div></div>
      <div class="xs muted"><i class="bi bi-clock-history"></i> อัปเดตทุก 15 นาที</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div style="border:1px solid #dfe4ec;border-radius:8px;padding:12px;display:flex;flex-direction:column;gap:4px"><span class="xs muted">เว็บไซต์</span><span style="display:flex;align-items:center;gap:6px;font-weight:600;color:#0f5132"><span class="dot"></span>ออนไลน์</span></div>
        <div style="border:1px solid #dfe4ec;border-radius:8px;padding:12px;display:flex;flex-direction:column;gap:4px"><span class="xs muted">ฐานข้อมูล</span><span style="display:flex;align-items:center;gap:6px;font-weight:600;color:#0f5132"><span class="dot"></span>พร้อมใช้งาน</span></div>
      </div>
      <div class="term"><span class="k">host</span> <?= e(Settings::get('sftp_host')) ?>
<span class="k">port</span> 22
<span class="k">user</span> somchai01</div>
    </div>
  </div>
</div></section>

<section id="features" class="sec" style="padding-bottom:72px">
  <div style="display:flex;flex-direction:column;gap:12px;max-width:640px;margin-bottom:48px">
    <span class="eyebrow">ฟังก์ชันการใช้งาน</span>
    <h2>ทุกอย่างที่ต้องใช้ในการเผยแพร่เว็บไซต์ของนักศึกษา</h2>
  </div>
  <div class="feat-grid">
    <div><i class="bi bi-person-plus"></i><h3>สมัครด้วยตนเอง (Self-Service)</h3><p>กรอกข้อมูลนักศึกษา ตรวจสอบ username แบบทันที และยืนยันตัวตนผ่านอีเมลสถาบัน</p></div>
    <div><i class="bi bi-cpu"></i><h3>สร้างบัญชีอัตโนมัติ (Provisioning)</h3><p>ระบบสร้างบัญชี Linux, ฐานข้อมูล MariaDB และ Subdomain ให้ทันทีหลังยืนยันอีเมล</p></div>
    <div><i class="bi bi-pie-chart"></i><h3>ติดตามโควตา (Quota)</h3><p>ดูพื้นที่ที่ใช้ไปเทียบกับโควตาทั้งหมด พร้อมเวลาอัปเดตล่าสุดทุก 15 นาที</p></div>
    <div><i class="bi bi-hdd-network"></i><h3>อัปโหลดไฟล์ผ่าน SFTP</h3><p>ข้อมูลเชื่อมต่อ FileZilla ครบถ้วน คัดลอกหรือดาวน์โหลดเก็บไว้ได้ พร้อมคู่มือ</p></div>
    <div><i class="bi bi-database"></i><h3>ฐานข้อมูล MariaDB</h3><p>ได้รับฐานข้อมูลส่วนตัว 1 ชุด เปลี่ยนรหัสผ่านฐานข้อมูลได้เองจากหน้าตั้งค่า</p></div>
    <div><i class="bi bi-shield-check"></i><h3>แผงควบคุมผู้ดูแลระบบ</h3><p>อาจารย์และเจ้าหน้าที่อนุมัติ ระงับ ปรับโควตา และดูภาพรวมทรัพยากรทั้งเซิร์ฟเวอร์</p></div>
  </div>
</section>

<section id="steps" style="background:#f3f5f9"><div class="sec">
  <h2 style="margin-bottom:48px">เริ่มใช้งานใน 4 ขั้นตอน</h2>
  <div class="steps">
    <div><span class="mono c-blue small">01</span><h3>กรอกแบบฟอร์ม</h3><p>ชื่อ-นามสกุล รหัสนักศึกษา อีเมลสถาบัน และ username ที่ต้องการ</p></div>
    <div><span class="mono c-blue small">02</span><h3>ยืนยันอีเมล</h3><p>กรอกรหัส OTP 6 หลักที่ส่งไปยังอีเมลสถาบันของคุณ</p></div>
    <div><span class="mono c-blue small">03</span><h3>ระบบสร้างบัญชี</h3><p>บัญชี SFTP ฐานข้อมูล และ URL เว็บไซต์ ถูกสร้างบนเซิร์ฟเวอร์จริง</p></div>
    <div><span class="mono c-blue small">04</span><h3>อัปโหลดเว็บไซต์</h3><p>เชื่อมต่อด้วย FileZilla แล้วอัปโหลดไฟล์ไปที่โฟลเดอร์ public_html</p></div>
  </div>
</div></section>

<section id="specs" class="sec">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr));gap:48px;align-items:start">
    <div style="display:flex;flex-direction:column;gap:16px">
      <h2>สิ่งที่นักศึกษาได้รับต่อ 1 บัญชี</h2>
      <p class="muted" style="margin:0;line-height:1.7;font-size:16px">ทรัพยากรทั้งหมดผูกกับ username เดียวกัน และเปลี่ยน username ไม่ได้หลังยืนยันตัวตนแล้ว</p>
    </div>
    <div class="spec-list">
      <div><span>พื้นที่เว็บ (Disk Quota)</span><strong><?= Settings::int('default_soft_mb') ?> MB</strong></div>
      <div><span>ฐานข้อมูล MariaDB</span><strong>1 ฐานข้อมูล</strong></div>
      <div><span>การเชื่อมต่อไฟล์</span><strong>SSH / SFTP</strong></div>
      <div><span>รองรับภาษา</span><strong>PHP, HTML, CSS, JS</strong></div>
      <div><span>URL เว็บไซต์</span><strong class="mono" style="font-size:14px">username.<?= e($domain) ?></strong></div>
    </div>
  </div>
</section>

<section class="cta">
  <div style="max-width:1200px;margin:0 auto;padding:72px 24px;display:flex;flex-wrap:wrap;gap:24px;align-items:center;justify-content:space-between">
    <div style="display:flex;flex-direction:column;gap:8px"><h2 style="margin:0;font-size:30px">พร้อมเริ่มสร้างเว็บไซต์แรกแล้วหรือยัง</h2><p style="margin:0;color:#c9d6ee">ใช้เวลาสมัครไม่เกิน 3 นาที</p></div>
    <a class="btn white lg" href="<?= e(url('signup')) ?>">สมัครใช้งาน</a>
  </div>
  <div style="border-top:1px solid rgba(255,255,255,.1)">
    <div style="max-width:1200px;margin:0 auto;padding:20px 24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;font-size:13px;color:#9fb3d9">
      <span style="display:flex;align-items:center;gap:10px"><img src="<?= $logo ?>" alt="" style="width:28px;height:28px">© <?= (int)date('Y') + 543 ?> Student Hosting Portal · ศูนย์เทคโนโลยีสารสนเทศ</span>
      <span>ให้บริการบน TurnKey GNU/Linux LAMP Stack</span>
    </div>
  </div>
</section>
