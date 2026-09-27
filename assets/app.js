(function () {
  'use strict';
  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

  // Sidebar collapse (remembered per browser)
  const app = $('.app');
  if (app) {
    try { if (localStorage.getItem('shp.side') === '0') app.classList.add('collapsed'); } catch (e) {}
    $$('[data-toggle-side]').forEach(b => b.addEventListener('click', () => {
      app.classList.toggle('collapsed');
      try { localStorage.setItem('shp.side', app.classList.contains('collapsed') ? '0' : '1'); } catch (e) {}
    }));
  }

  // Toasts auto-hide
  $$('.toast[data-autohide]').forEach(t => setTimeout(() => t.remove(), 4000));

  // Copy buttons
  $$('[data-copy]').forEach(b => b.addEventListener('click', () => {
    const v = b.getAttribute('data-copy');
    const done = () => { const i = $('i', b); if (!i) return; const c = i.className; i.className = 'bi bi-check2'; setTimeout(() => i.className = c, 1500); };
    if (navigator.clipboard) navigator.clipboard.writeText(v).then(done, done); else done();
  }));

  // Download text built from [data-conn] rows
  $$('[data-download-txt]').forEach(b => b.addEventListener('click', () => {
    const rows = $$('[data-conn-row]').map(r => r.getAttribute('data-label') + ': ' + r.getAttribute('data-value'));
    const txt = 'Student Hosting Portal\n\n' + rows.join('\n') + '\n';
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([txt], { type: 'text/plain;charset=utf-8' }));
    a.download = b.getAttribute('data-download-txt');
    a.click();
  }));
  $$('[data-print]').forEach(b => b.addEventListener('click', () => window.print()));

  // Confirm modal for any <form data-confirm="...">
  const modal = $('#confirm-modal');
  let pendingForm = null;
  if (modal) {
    const typeWrap = $('.modal-type', modal), typeInput = $('input', typeWrap), okBtn = $('[data-ok]', modal);
    const close = () => { modal.classList.add('hidden'); pendingForm = null; };
    const sync = () => { okBtn.disabled = !typeWrap.classList.contains('hidden') && typeInput.value !== typeInput.dataset.target; };
    typeInput.addEventListener('input', sync);
    $$('[data-cancel]', modal).forEach(b => b.addEventListener('click', close));
    modal.addEventListener('click', e => { if (e.target === modal) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });
    okBtn.addEventListener('click', () => { if (pendingForm) { const f = pendingForm; pendingForm = null; f.dataset.confirmed = '1'; f.requestSubmit ? f.requestSubmit() : f.submit(); } });

    document.addEventListener('submit', e => {
      const f = e.target;
      if (!f.matches('form[data-confirm]') || f.dataset.confirmed === '1') return;
      e.preventDefault();
      pendingForm = f;
      const kind = f.dataset.kind || 'info';
      $('h3', modal).textContent = f.dataset.confirm;
      $('p', modal).textContent = f.dataset.body || '';
      const ic = $('.modal-ic', modal);
      ic.className = 'modal-ic ' + (kind === 'danger' ? 'danger' : kind === 'warn' ? 'warn' : '');
      $('i', ic).className = 'bi ' + (f.dataset.icon || (kind === 'danger' ? 'bi-exclamation-octagon' : 'bi-question-circle'));
      okBtn.textContent = f.dataset.btn || 'ยืนยัน';
      okBtn.className = 'btn ' + (kind === 'danger' ? 'danger' : 'primary');
      $('.note', modal).classList.toggle('hidden', f.dataset.server !== '1');
      if (f.dataset.type) {
        typeWrap.classList.remove('hidden');
        $('strong', typeWrap).textContent = f.dataset.type;
        typeInput.dataset.target = f.dataset.type;
        typeInput.value = '';
      } else typeWrap.classList.add('hidden');
      sync();
      modal.classList.remove('hidden');
      (f.dataset.type ? typeInput : okBtn).focus();
    });
  }

  // Terms modal
  const terms = $('#terms-modal');
  if (terms) {
    $$('[data-open-terms]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); terms.classList.remove('hidden'); }));
    $$('[data-close-terms]', terms).forEach(b => b.addEventListener('click', () => terms.classList.add('hidden')));
    $('[data-accept-terms]', terms).addEventListener('click', () => { const c = $('#agree'); if (c) { c.checked = true; c.dispatchEvent(new Event('change')); } terms.classList.add('hidden'); });
  }

  // Signup: live username check + submit enable
  const su = $('#signup-form');
  if (su) {
    const u = $('[name=username]', su), grp = $('.input-group', su), msg = $('#u-msg'), btn = $('[type=submit]', su);
    let ok = false, timer = null;
    const set = (state, text) => {
      grp.style.borderColor = state === 'ok' ? '#198754' : state === 'bad' ? '#dc3545' : '';
      msg.style.color = state === 'ok' ? '#146c43' : state === 'bad' ? '#b02a37' : '';
      $('i', msg).className = 'bi ' + (state === 'ok' ? 'bi-check-circle' : state === 'bad' ? 'bi-x-circle' : 'bi-info-circle');
      $('span', msg).textContent = text;
    };
    const valid = () => {
      const f = new FormData(su);
      btn.disabled = !(ok && f.get('full_name').trim() && f.get('student_id').trim().length >= 5 && /@/.test(f.get('email')) &&
        f.get('password').length >= 8 && f.get('password') === f.get('password2') && $('#agree').checked);
    };
    u.addEventListener('input', () => {
      u.value = u.value.toLowerCase().replace(/\s/g, '');
      ok = false; valid();
      clearTimeout(timer);
      const v = u.value;
      if (!v) return set('', 'ใช้ได้เฉพาะ a-z และ 0-9 ความยาว 4–16 ตัวอักษร');
      if (!/^[a-z0-9]+$/.test(v)) return set('bad', 'มีอักขระที่ไม่อนุญาต ใช้ได้เฉพาะ a-z และ 0-9');
      if (v.length < 4) return set('bad', 'สั้นเกินไป ต้องมีอย่างน้อย 4 ตัวอักษร');
      set('', 'กำลังตรวจสอบ…');
      timer = setTimeout(() => fetch(su.dataset.check + '&u=' + encodeURIComponent(v)).then(r => r.json()).then(j => {
        if (u.value !== v) return;
        ok = j.ok; set(j.ok ? 'ok' : 'bad', j.msg); valid();
      }).catch(() => set('', 'ตรวจสอบไม่ได้ ระบบจะตรวจอีกครั้งตอนส่งแบบฟอร์ม')), 300);
    });
    su.addEventListener('input', valid);
    su.addEventListener('change', valid);
    if (u.value) u.dispatchEvent(new Event('input'));
    valid();
  }

  // OTP: digits only, resend countdown
  const otp = $('.otp-input');
  if (otp) {
    const btn = $('#otp-submit');
    const f = () => { otp.value = otp.value.replace(/\D/g, '').slice(0, 6); btn.disabled = otp.value.length !== 6; };
    otp.addEventListener('input', f); f();
  }
  const cd = $('[data-countdown]');
  if (cd) {
    let n = +cd.dataset.countdown;
    const tick = () => {
      if (n <= 0) { $('#resend-wait').classList.add('hidden'); $('#resend-form').classList.remove('hidden'); return; }
      $('span', cd).textContent = n; n--; setTimeout(tick, 1000);
    };
    tick();
  }

  // Password pair hint (settings)
  $$('[data-pw-form]').forEach(fm => {
    const a = $('[name=password]', fm), b = $('[name=password2]', fm), h = $('.pw-hint', fm), s = $('[type=submit]', fm);
    const upd = () => {
      const ok = a.value.length >= 8 && a.value === b.value;
      h.textContent = !a.value ? 'อย่างน้อย 8 ตัวอักษร' : a.value.length < 8 ? 'สั้นเกินไป (อย่างน้อย 8 ตัวอักษร)' : b.value && a.value !== b.value ? 'รหัสผ่านไม่ตรงกัน' : ok ? 'พร้อมบันทึก' : 'กรุณายืนยันรหัสผ่าน';
      h.style.color = ok ? '#146c43' : (a.value && (a.value.length < 8 || (b.value && a.value !== b.value))) ? '#b02a37' : '';
      s.disabled = !ok;
    };
    a.addEventListener('input', upd); b.addEventListener('input', upd); upd();
  });

  // Quota form validation
  $$('[data-quota-form]').forEach(fm => {
    const s = $('[name=soft]', fm), h = $('[name=hard]', fm), btn = $('[type=submit]', fm), hint = $('.q-hint', fm);
    const upd = () => {
      const bad = !(+s.value > 0 && +h.value >= +s.value && +h.value <= 5000);
      btn.disabled = bad;
      hint.textContent = bad ? 'Hard limit ต้องมากกว่าหรือเท่ากับ Soft limit และไม่เกิน 5000 MB' : hint.dataset.def;
      hint.style.color = bad ? '#b02a37' : '';
      fm.dataset.body = 'ปรับโควตาของ ' + fm.dataset.user + ' เป็น Soft ' + s.value + ' MB / Hard ' + h.value + ' MB';
    };
    s.addEventListener('input', upd); h.addEventListener('input', upd); upd();
  });

  // Auto-submit filters
  $$('[data-autosubmit]').forEach(el => el.addEventListener('change', () => el.form.submit()));
})();
