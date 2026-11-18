// Telesom LearnHub service worker: lets the site be installed and shows a friendly page when offline.
// Pages are NEVER cached (they contain private data); only static files are kept as an offline fallback.
const VERSION = 'learnhub-v2';
const PRECACHE = ['offline.html', 'assets/css/style.css', 'assets/css/theme.css', 'assets/js/app.js', 'assets/icons/icon-192.png'];
self.addEventListener('install', e => { e.waitUntil(caches.open(VERSION).then(c => c.addAll(PRECACHE)).then(() => self.skipWaiting())); });
self.addEventListener('activate', e => { e.waitUntil(caches.keys().then(ks => Promise.all(ks.filter(k => k !== VERSION).map(k => caches.delete(k)))).then(() => self.clients.claim())); });
self.addEventListener('fetch', e => {
  const r = e.request;
  if (r.method !== 'GET') return;
  const u = new URL(r.url);
  if (u.origin !== self.location.origin) return;
  if (r.mode === 'navigate') { e.respondWith(fetch(r).catch(() => caches.match('offline.html'))); return; }
  if (u.pathname.indexOf('/assets/') !== -1) {          // static files: network first, cached copy when offline
    e.respondWith(fetch(r).then(res => { if (res.ok) { const copy = res.clone(); caches.open(VERSION).then(c => c.put(r, copy)); } return res; }).catch(() => caches.match(r)));
  }
});
