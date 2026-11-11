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

// auth pages: show/hide password, demo fill, strength meter
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.eye').forEach(function (b) {
    b.addEventListener('click', function () { var i = b.parentNode.querySelector('input'); i.type = i.type === 'password' ? 'text' : 'password'; });
  });
  document.querySelectorAll('[data-demo]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.getElementById('email').value = b.dataset.demo; document.getElementById('password').value = 'password';
      document.querySelectorAll('[data-demo]').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on');
    });
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

// night / day mode + collapsible sidebar (both remembered in this browser)
document.addEventListener('DOMContentLoaded', function () {
  var root = document.documentElement;
  function setTheme(t) {
    root.setAttribute('data-theme', t);
    try { localStorage.setItem('lh_theme', t); } catch (e) {}
    document.querySelectorAll('.theme-btn').forEach(function (b) {
      b.querySelector('.ti').textContent = t === 'dark' ? '☀️' : '🌙';
      b.title = t === 'dark' ? 'Switch to day mode' : 'Switch to night mode';
    });
  }
  setTheme(root.getAttribute('data-theme') || 'light');
  document.querySelectorAll('.theme-btn').forEach(function (b) {
    b.addEventListener('click', function () { setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'); });
  });
  var st = document.getElementById('sideToggle');
  if (st) st.addEventListener('click', function () {
    var c = root.classList.toggle('side-c');
    try { localStorage.setItem('lh_side', c ? '1' : '0'); } catch (e) {}
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
