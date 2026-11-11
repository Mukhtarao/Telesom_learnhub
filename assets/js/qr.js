/* Minimal offline QR Code generator (byte mode, error correction M, versions 1-10). Usage: QR.svg(text, {fg, bg, margin}) */
var QR = (function () {
  var ECC = [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26], BLK = [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5];
  function mul(x, y) { var z = 0; for (var i = 7; i >= 0; i--) { z = (z << 1) ^ ((z >>> 7) * 0x11D); z ^= ((y >>> i) & 1) * x; } return z & 255; }
  function divisor(d) { var r = []; for (var i = 0; i < d; i++) r.push(0); r[d - 1] = 1; var root = 1;
    for (var i = 0; i < d; i++) { for (var j = 0; j < d; j++) { r[j] = mul(r[j], root); if (j + 1 < d) r[j] ^= r[j + 1]; } root = mul(root, 2); } return r; }
  function remainder(data, div) { var r = div.map(function () { return 0; });
    data.forEach(function (b) { var f = b ^ r.shift(); r.push(0); div.forEach(function (c, i) { r[i] ^= mul(c, f); }); }); return r; }
  function rawModules(v) { var n = (16 * v + 128) * v + 64; if (v >= 2) { var a = Math.floor(v / 7) + 2; n -= (25 * a - 10) * a - 55; if (v >= 7) n -= 36; } return n; }
  function utf8(s) { return Array.from(unescape(encodeURIComponent(s))).map(function (c) { return c.charCodeAt(0); }); }
  function build(text) {
    var bytes = utf8(text), ver, cap;
    for (ver = 1; ver <= 10; ver++) { cap = Math.floor(rawModules(ver) / 8) - ECC[ver] * BLK[ver]; if (4 + (ver < 10 ? 8 : 16) + bytes.length * 8 <= cap * 8) break; }
    if (ver > 10) throw new Error('Text too long for QR');
    var bits = []; function put(v, n) { for (var i = n - 1; i >= 0; i--) bits.push((v >>> i) & 1); }
    put(4, 4); put(bytes.length, ver < 10 ? 8 : 16); bytes.forEach(function (b) { put(b, 8); });
    put(0, Math.min(4, cap * 8 - bits.length)); while (bits.length % 8) bits.push(0);
    for (var pad = 0xEC; bits.length < cap * 8; pad ^= 0xEC ^ 0x11) put(pad, 8);
    var data = []; for (var i = 0; i < bits.length; i += 8) { var b = 0; for (var k = 0; k < 8; k++) b = (b << 1) | bits[i + k]; data.push(b); }
    // error correction + interleave
    var nb = BLK[ver], el = ECC[ver], raw = Math.floor(rawModules(ver) / 8), ns = nb - raw % nb, sl = Math.floor(raw / nb), blocks = [], div = divisor(el), k = 0;
    for (var i = 0; i < nb; i++) { var d = data.slice(k, k + sl - el + (i < ns ? 0 : 1)); k += d.length; var e = remainder(d, div); if (i < ns) d.push(0); blocks.push(d.concat(e)); }
    var out = []; for (var i = 0; i < blocks[0].length; i++) blocks.forEach(function (bl, j) { if (i !== sl - el || j >= ns) out.push(bl[i]); });
    // matrix
    var size = ver * 4 + 17, M = [], F = []; for (var y = 0; y < size; y++) { M.push(new Array(size).fill(false)); F.push(new Array(size).fill(false)); }
    function set(x, y, d) { M[y][x] = d; F[y][x] = true; }
    for (var i = 0; i < size; i++) { set(6, i, i % 2 === 0); set(i, 6, i % 2 === 0); }
    function finder(cx, cy) { for (var dy = -4; dy <= 4; dy++) for (var dx = -4; dx <= 4; dx++) { var x = cx + dx, y = cy + dy; if (x < 0 || y < 0 || x >= size || y >= size) continue; var dist = Math.max(Math.abs(dx), Math.abs(dy)); set(x, y, dist !== 2 && dist !== 4); } }
    finder(3, 3); finder(size - 4, 3); finder(3, size - 4);
    var ap = []; if (ver > 1) { var na = Math.floor(ver / 7) + 2, step = Math.ceil((ver * 4 + 4) / (na * 2 - 2)) * 2; ap = [6]; for (var p = size - 7; ap.length < na; p -= step) ap.splice(1, 0, p); }
    ap.forEach(function (ax, i) { ap.forEach(function (ay, j) { if ((i === 0 && j === 0) || (i === 0 && j === ap.length - 1) || (i === ap.length - 1 && j === 0)) return;
      for (var dy = -2; dy <= 2; dy++) for (var dx = -2; dx <= 2; dx++) set(ax + dx, ay + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1); }); });
    function fmt(mask) { var d = mask, r = d; for (var i = 0; i < 10; i++) r = (r << 1) ^ ((r >>> 9) * 0x537); var bs = ((d << 10) | r) ^ 0x5412; function g(i) { return ((bs >>> i) & 1) !== 0; }
      for (var i = 0; i <= 5; i++) set(8, i, g(i)); set(8, 7, g(6)); set(8, 8, g(7)); set(7, 8, g(8)); for (var i = 9; i < 15; i++) set(14 - i, 8, g(i));
      for (var i = 0; i < 8; i++) set(size - 1 - i, 8, g(i)); for (var i = 8; i < 15; i++) set(8, size - 15 + i, g(i)); set(8, size - 8, true); }
    fmt(0);
    if (ver >= 7) { var r = ver; for (var i = 0; i < 12; i++) r = (r << 1) ^ ((r >>> 11) * 0x1F25); var vb = (ver << 12) | r;
      for (var i = 0; i < 18; i++) { var c = ((vb >>> i) & 1) !== 0, a = size - 11 + i % 3, b = Math.floor(i / 3); set(a, b, c); set(b, a, c); } }
    var idx = 0; for (var right = size - 1; right >= 1; right -= 2) { if (right === 6) right = 5;
      for (var v = 0; v < size; v++) for (var j = 0; j < 2; j++) { var x = right - j, y = ((right + 1) & 2) === 0 ? size - 1 - v : v;
        if (!F[y][x] && idx < out.length * 8) { M[y][x] = ((out[idx >>> 3] >>> (7 - (idx & 7))) & 1) !== 0; idx++; } } }
    var masks = [function (x, y) { return (x + y) % 2 === 0; }, function (x, y) { return y % 2 === 0; }, function (x, y) { return x % 3 === 0; }, function (x, y) { return (x + y) % 3 === 0; },
      function (x, y) { return (Math.floor(x / 3) + Math.floor(y / 2)) % 2 === 0; }, function (x, y) { return x * y % 2 + x * y % 3 === 0; },
      function (x, y) { return (x * y % 2 + x * y % 3) % 2 === 0; }, function (x, y) { return ((x + y) % 2 + x * y % 3) % 2 === 0; }];
    function apply(m) { for (var y = 0; y < size; y++) for (var x = 0; x < size; x++) if (!F[y][x] && masks[m](x, y)) M[y][x] = !M[y][x]; }
    function penalty() { var p = 0, dark = 0;
      for (var pass = 0; pass < 2; pass++) for (var a = 0; a < size; a++) { var run = 1; for (var b = 1; b < size; b++) { var c = pass ? M[b][a] : M[a][b], q = pass ? M[b - 1][a] : M[a][b - 1]; if (c === q) { run++; if (run === 5) p += 3; else if (run > 5) p++; } else run = 1; } }
      for (var y = 0; y < size; y++) for (var x = 0; x < size; x++) { if (M[y][x]) dark++; if (x && y && M[y][x] === M[y][x - 1] && M[y][x] === M[y - 1][x] && M[y][x] === M[y - 1][x - 1]) p += 3; }
      return p + Math.floor(Math.abs(dark * 20 - size * size * 10) / (size * size)) * 10; }
    var best = 0, bp = 1e9; for (var m = 0; m < 8; m++) { apply(m); fmt(m); var pn = penalty(); if (pn < bp) { bp = pn; best = m; } apply(m); }
    apply(best); fmt(best); return M;
  }
  function svg(text, o) { o = o || {}; var M = build(text), n = M.length, mg = o.margin == null ? 2 : o.margin, d = '';
    for (var y = 0; y < n; y++) for (var x = 0; x < n; x++) if (M[y][x]) d += 'M' + (x + mg) + ',' + (y + mg) + 'h1v1h-1z';
    var t = n + mg * 2; return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + t + ' ' + t + '" shape-rendering="crispEdges"><rect width="' + t + '" height="' + t + '" fill="' + (o.bg || '#fff') + '"/><path d="' + d + '" fill="' + (o.fg || '#000') + '"/></svg>'; }
  return { build: build, svg: svg };
})();
if (typeof module !== 'undefined') module.exports = QR;
