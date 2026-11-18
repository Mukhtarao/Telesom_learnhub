document.addEventListener('DOMContentLoaded', function () {
  // confirm dialogs
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
  });
  // quiz countdown
  var t = document.getElementById('timer');
  if (t) {
    var s = parseInt(t.dataset.seconds, 10), f = document.getElementById('quizform');
    (function tick() {
      var m = Math.floor(s / 60), r = s % 60;
      t.textContent = '⏱ ' + m + ':' + (r < 10 ? '0' : '') + r;
      if (s-- <= 0) { f.submit(); return; }
      setTimeout(tick, 1000);
    })();
  }
  // animated counters on landing page
  document.querySelectorAll('[data-count]').forEach(function (el) {
    var end = parseInt(el.dataset.count, 10), cur = 0, step = Math.max(1, Math.ceil(end / 40));
    var iv = setInterval(function () { cur += step; if (cur >= end) { cur = end; clearInterval(iv); } el.textContent = cur + (el.dataset.suffix || ''); }, 30);
  });
  // question type toggle (course editor)
  var qt = document.getElementById('qtype');
  if (qt) {
    var upd = function () {
      document.querySelectorAll('[data-for]').forEach(function (b) { b.style.display = b.dataset.for.split(' ').indexOf(qt.value) > -1 ? '' : 'none'; });
    };
    qt.addEventListener('change', upd); upd();
  }
  // lesson type toggle
  var lt = document.getElementById('ltype');
  if (lt) {
    var u2 = function () {
      document.querySelectorAll('[data-lt]').forEach(function (b) { b.style.display = b.dataset.lt.split(' ').indexOf(lt.value) > -1 ? '' : 'none'; });
    };
    lt.addEventListener('change', u2); u2();
  }
  // chat scroll
  var c = document.querySelector('.chat'); if (c) c.scrollTop = c.scrollHeight;
});

// scroll-reveal for public sections and cards
document.addEventListener('DOMContentLoaded', function () {
  var els = document.querySelectorAll('.pub .feat, .pub .cc, .pub .step, .pub .two > *, .pub .cta, .pub .sec-h');
  if (!('IntersectionObserver' in window)) return;
  var io = new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: .08 });
  els.forEach(function (el, i) { el.classList.add('rv'); el.style.transitionDelay = (i % 4) * 60 + 'ms'; io.observe(el); });
});

// auth pages: show/hide password, strength meter
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.eye').forEach(function (b) {
    b.addEventListener('click', function () { var i = b.parentNode.querySelector('input'); i.type = i.type === 'password' ? 'text' : 'password'; });
  });
  var pw = document.getElementById('pw');
  if (pw) pw.addEventListener('input', function () {
    var v = pw.value, s = 0; if (v.length >= 6) s++; if (v.length >= 10) s++; if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++; if (/\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
    var bar = document.getElementById('pwbar'), t = document.getElementById('pwtxt');
    bar.style.width = (s * 20) + '%'; bar.style.background = s < 2 ? '#e11d48' : s < 4 ? '#f59e0b' : '#059669';
    t.textContent = v ? ['', 'Too weak', 'Weak', 'Fair', 'Good', 'Strong 💪'][s] || 'Too weak' : 'Use letters, numbers and symbols for a strong password.';
  });
});


// lesson player: learner must finish the video/audio before the lesson can be completed
document.addEventListener('DOMContentLoaded', function () {
  var box = document.getElementById('lessonPlayer'), btn = document.getElementById('completeBtn'), gate = document.getElementById('gate');
  if (!box || !btn || !gate || gate.classList.contains('ok')) return;
  var span = gate.querySelector('span'), bar = gate.querySelector('i'), prov = box.dataset.provider, maxT = 0, dur = 0, sent = false;
  function show(p) { bar.style.width = p + '%'; if (p < 100) span.textContent = '🔒 Watched ' + p + '% — finish it to unlock “Complete & continue”'; }
  function prog(t, d) { if (d) dur = d; if (t > maxT) maxT = t; if (dur) show(Math.min(100, Math.round(maxT / dur * 100))); }
  function unlock() {
    if (sent || !(dur > 0 && maxT >= dur * 0.85)) return; sent = true;
    var fd = new FormData(); fd.append('csrf', box.dataset.csrf); fd.append('lesson', box.dataset.lesson); fd.append('duration', Math.round(dur));
    fetch('watch.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (j.ok) { btn.disabled = false; btn.textContent = 'Complete & continue ✔'; gate.classList.add('ok'); bar.style.width = '100%'; span.textContent = '✅ Well done — you finished it. You can continue now.'; }
      else { sent = false; span.textContent = '⚠ ' + (j.msg || 'Could not verify.'); setTimeout(unlock, 6000); }
    }).catch(function () { sent = false; span.textContent = '⚠ Connection problem — retrying…'; setTimeout(unlock, 6000); });
  }
  if (prov === 'html5') {
    var m = box.querySelector('video,audio'); if (!m) return;
    m.addEventListener('loadedmetadata', function () { dur = m.duration || 0; });
    m.addEventListener('timeupdate', function () { if (!m.seeking && m.currentTime - maxT < 2.5) prog(m.currentTime, m.duration); });
    m.addEventListener('seeking', function () { if (m.currentTime > maxT + 1.5) m.currentTime = maxT; });
    m.addEventListener('ended', function () { if (maxT >= m.duration * 0.85) { prog(m.duration, m.duration); unlock(); } });
    m.addEventListener('error', function () { span.textContent = '⚠ This file cannot be played in your browser. Try Chrome or Edge, or tell your instructor.'; });
  } else if (prov === 'youtube') {
    var yt, timer;
    window.onYouTubeIframeAPIReady = function () {
      yt = new YT.Player('lessonFrame', { events: { onStateChange: function (e) {
        if (e.data === 1 && !timer) timer = setInterval(function () { var t = yt.getCurrentTime(), d = yt.getDuration(); if (t > maxT + 3) yt.seekTo(maxT, true); else prog(t, d); }, 500);
        if (e.data === 0 && maxT >= yt.getDuration() * 0.85) { prog(yt.getDuration(), yt.getDuration()); unlock(); }
      } } });
    };
    var ys = document.createElement('script'); ys.src = 'https://www.youtube.com/iframe_api'; document.head.appendChild(ys);
  } else if (prov === 'vimeo') {
    var vs = document.createElement('script'); vs.src = 'https://player.vimeo.com/api/player.js';
    vs.onload = function () {
      var p = new Vimeo.Player(document.getElementById('lessonFrame'));
      p.getDuration().then(function (d) { dur = d; });
      p.on('timeupdate', function (e) { if (e.seconds > maxT + 3) p.setCurrentTime(maxT); else prog(e.seconds, e.duration); });
      p.on('ended', function () { if (maxT >= dur * 0.85) { prog(dur, dur); unlock(); } });
    };
    document.head.appendChild(vs);
  }
});

// night / day mode (remembered in this browser)
document.addEventListener('DOMContentLoaded', function () {
  var root = document.documentElement;
  function setTheme(t) {
    root.setAttribute('data-theme', t);
    try { localStorage.setItem('lh_theme', t); } catch (e) {}
    document.querySelectorAll('.theme-btn').forEach(function (b) {
      b.querySelector('.ti').textContent = t === 'dark' ? '☀️' : '🌙';
      var tl = b.querySelector('.tl'); if (tl && b.dataset.day) tl.textContent = t === 'dark' ? b.dataset.day : b.dataset.night;
      b.title = t === 'dark' ? 'Switch to day mode' : 'Switch to night mode';
    });
  }
  setTheme(root.getAttribute('data-theme') || 'light');
  document.querySelectorAll('.theme-btn').forEach(function (b) {
    b.addEventListener('click', function () { setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'); });
  });
});

// live classes: countdown (uses the server clock so a wrong PC clock does not matter)
document.addEventListener('DOMContentLoaded', function () {
  var els = document.querySelectorAll('.lv-count'); if (!els.length) return;
  var off = (+els[0].dataset.now) - Math.floor(Date.now() / 1000), reloaded = false;
  function fmt(s) { var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60), x = s % 60;
    return (d ? d + 'd ' : '') + (d || h ? h + 'h ' : '') + m + 'm' + (d ? '' : ' ' + x + 's'); }
  function tick() {
    var now = Math.floor(Date.now() / 1000) + off;
    els.forEach(function (el) {
      var s = +el.dataset.start, e = +el.dataset.end;
      if (now < s) { el.textContent = '⏳ Starts in ' + fmt(s - now); if (!reloaded && el.dataset.joinable === '0' && s - now <= 900) { reloaded = true; location.reload(); } }
      else if (now <= e) el.textContent = '🔴 In progress — ends in ' + fmt(e - now);
      else el.textContent = 'Class time is over';
    });
  }
  tick(); setInterval(tick, 1000);
});

// monthly / yearly plan toggle
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.plans-root').forEach(function (root) {
    root.querySelectorAll('[data-bill]').forEach(function (b) {
      b.addEventListener('click', function () {
        root.querySelectorAll('[data-bill]').forEach(function (x) { x.classList.toggle('on', x === b); });
        root.querySelectorAll('.plan-grid').forEach(function (g) { g.style.display = g.dataset.b === b.dataset.bill ? '' : 'none'; });
      });
    });
  });
});

// toasts: session messages slide in at the top right and fade away
document.addEventListener('DOMContentLoaded', function () {
  var items = document.querySelectorAll('.flash.toast'); if (!items.length) return;
  var box = document.createElement('div'); box.id = 'toasts'; box.setAttribute('aria-live', 'polite'); document.body.appendChild(box);
  items.forEach(function (el, i) {
    box.appendChild(el);
    var life = el.classList.contains('error') ? 9000 : 6000, t;
    el.style.setProperty('--life', life + 'ms');
    function close() { clearTimeout(t); el.classList.add('out'); setTimeout(function () { el.remove(); }, 320); }
    t = setTimeout(close, life + i * 250);
    el.addEventListener('mouseenter', function () { clearTimeout(t); el.style.setProperty('--life', '3600s'); });
    el.addEventListener('mouseleave', function () { t = setTimeout(close, 2500); });
  });
});

// forms: show a spinner and block double submits (important for payments)
document.addEventListener('submit', function (e) {
  var f = e.target; if (!f || f.tagName !== 'FORM' || (f.method || '').toLowerCase() !== 'post' || f.hasAttribute('data-noload')) return;
  if (f.dataset.sent) { e.preventDefault(); return; }
  var sub = e.submitter;
  setTimeout(function () {                      // after the browser has collected the form data
    if (e.defaultPrevented) return;
    f.dataset.sent = '1';
    if (sub) sub.classList.add('loading');
    f.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
  }, 0);
});
window.addEventListener('pageshow', function (ev) {  // coming back with the browser Back button
  if (!ev.persisted) return;
  document.querySelectorAll('form[data-sent]').forEach(function (f) { delete f.dataset.sent; f.querySelectorAll('button').forEach(function (b) { b.disabled = false; b.classList.remove('loading'); }); });
});

// public pages: nav shadow on scroll + back-to-top button
document.addEventListener('DOMContentLoaded', function () {
  var nav = document.querySelector('.nav'); if (!nav) return;
  var up = document.createElement('button'); up.id = 'toTop'; up.type = 'button'; up.setAttribute('aria-label', 'Back to top'); up.textContent = '↑'; document.body.appendChild(up);
  up.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  function on() { nav.classList.toggle('scrolled', window.scrollY > 8); up.classList.toggle('show', window.scrollY > 700); }
  window.addEventListener('scroll', on, { passive: true }); on();
});

// installable app (PWA): service worker + "install" button
if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
  window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); });
}
var _installEvt = null;
window.addEventListener('beforeinstallprompt', function (e) {
  e.preventDefault(); _installEvt = e;
  document.querySelectorAll('.install-btn').forEach(function (b) { b.hidden = false; });
});
window.addEventListener('appinstalled', function () { document.querySelectorAll('.install-btn').forEach(function (b) { b.hidden = true; }); });
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.install-btn').forEach(function (b) {
    if (_installEvt) b.hidden = false;
    b.addEventListener('click', function () { if (!_installEvt) return; _installEvt.prompt(); _installEvt.userChoice.then(function () { _installEvt = null; b.hidden = true; }); });
  });
});


// profile menu (top bar): opens on click, closes on outside click / Escape
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('umBtn'), menu = document.getElementById('umMenu'); if (!btn || !menu) return;
  function set(open) { menu.hidden = !open; btn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
  btn.addEventListener('click', function (e) { e.stopPropagation(); set(menu.hidden); });
  document.addEventListener('click', function (e) { if (!menu.hidden && !menu.contains(e.target)) set(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) { set(false); btn.focus(); } });
});
