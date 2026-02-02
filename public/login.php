<?php





require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';


header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Content-Type: text/html; charset=UTF-8');

$UygulamaAdi = config('app.name', 'NbtProject');
$Logo = config('app.logo', '/assets/logo.png');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?> | Giriş</title>
  <link rel="stylesheet" href="/assets/bootstrap.min.css" />
</head>
<body class="bg-light">
  <div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-sm p-4" style="max-width:420px; width:100%;">
      <form id="loginForm" novalidate>
        <div class="text-center mb-3">
          <img id="brandLogo" src="<?= htmlspecialchars($Logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-height:80px;" class="mb-2" />
          <h5 class="mb-0" id="brandName"><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></h5>
          <small class="text-muted">Giriş yapın</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Kullanıcı</label>
          <input id="username" class="form-control" placeholder="kullanıcı" />
          <small class="text-muted">En az 3 karakter olmalı.</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Parola</label>
          <input id="password" type="password" class="form-control" placeholder="parola" />
          <small class="text-muted">En az 6 karakter olmalı.</small>
        </div>
        <div class="d-grid gap-2">
          <button id="btnLogin" class="btn btn-primary" type="submit">Giriş</button>
        </div>
        <div class="alert alert-danger d-none mt-3" role="alert" id="alertBox"></div>
      </form>
    </div>
  </div>

  <script>
    const UygulamaAyar = <?= json_encode(['name' => $UygulamaAdi, 'logo' => $Logo], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const AnahtarToken = 'nbt_token';
    const AnahtarRol = 'nbt_role';
    const AnahtarKullanici = 'nbt_user';
    const AnahtarSekme = 'nbt_tab_id';
    const AnahtarPermissions = 'nbt_permissions';

    document.title = `${UygulamaAyar.name || 'NbtProject'} | Giriş`;
    document.getElementById('brandName').textContent = UygulamaAyar.name || 'NbtProject';
    document.getElementById('brandLogo').src = UygulamaAyar.logo || '/assets/logo.png';

    function UUIDolustur() {
      // crypto.randomUUID varsa kullan (guvenli baglam)
      if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        try { return crypto.randomUUID(); } catch (e) { /* fallback */ }
      }
      // Fallback: Math.random tabanli UUID v4
      return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
      });
    }
    function SekmeIdAl() {
      let Deger = localStorage.getItem(AnahtarSekme);
      if (!Deger) { Deger = UUIDolustur(); localStorage.setItem(AnahtarSekme, Deger); }
      return Deger;
    }
    function HataGoster(Mesaj) {
      const Kutu = document.getElementById('alertBox');
      Kutu.textContent = Mesaj;
      Kutu.classList.remove('d-none');
    }
    function HataTemizle() {
      const Kutu = document.getElementById('alertBox');
      Kutu.classList.add('d-none');
      Kutu.textContent = '';
    }

    function ApiBaseAl() {
      const Yol = window.location.pathname || '/';
      // /subdir/login.php -> /subdir, /login.php -> ''
      let baseDir = '';
      if (Yol.endsWith('/login.php')) {
        baseDir = Yol.slice(0, -'/login.php'.length);
      } else if (Yol.endsWith('/login')) {
        baseDir = Yol.slice(0, -'/login'.length);
      }
      if (!baseDir) baseDir = '';
      // index.php kullaniliyorsa API'yi index.php ile cagir
      if (Yol.includes('/index.php') || Yol.endsWith('/login.php')) {
        return baseDir + '/index.php';
      }
      return baseDir;
    }

    async function GirisYap() {
      try {
        HataTemizle();
        const KullaniciAdi = document.getElementById('username').value.trim();
        const Parola = document.getElementById('password').value.trim();
        if (!KullaniciAdi) { HataGoster('Kullanıcı adı zorunludur.'); return; }
        if (KullaniciAdi.length < 3) { HataGoster('Kullanıcı adı en az 3 karakter olmalıdır.'); return; }
        if (!Parola) { HataGoster('Parola zorunludur.'); return; }
        if (Parola.length < 6) { HataGoster('Parola en az 6 karakter olmalıdır.'); return; }
        const ApiBase = ApiBaseAl();
        const Yanıt = await fetch(ApiBase + '/api/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Tab-Id': SekmeIdAl() },
          body: JSON.stringify({ username: KullaniciAdi, password: Parola })
        });
        const Veri = await Yanıt.json().catch(() => ({}));
        if (!Yanıt.ok) throw new Error(Veri.error || 'Giriş yapılamadı.');
        localStorage.removeItem(AnahtarPermissions);
        localStorage.setItem(AnahtarToken, Veri.token);
        // Cookie 7 gün geçerli olsun (token TTL ile uyumlu)
        const ExpireDate = new Date();
        ExpireDate.setDate(ExpireDate.getDate() + 7);
        document.cookie = `nbt_token=${encodeURIComponent(Veri.token)}; path=/; expires=${ExpireDate.toUTCString()}; samesite=lax`;
        if (Veri.user?.role) localStorage.setItem(AnahtarRol, Veri.user.role);
        if (Veri.user) localStorage.setItem(AnahtarKullanici, JSON.stringify(Veri.user));
        window.location.href = '/';
      } catch (Hata) {
        HataGoster(Hata.message);
      }
    }

    async function TokenKontrol() {
      const Token = localStorage.getItem(AnahtarToken);
      if (!Token) return;
      
      try {
        const ApiBase = ApiBaseAl();
        const Yanit = await fetch(ApiBase + '/api/refresh', {
          method: 'POST',
          headers: { 
            'Authorization': 'Bearer ' + Token,
            'Content-Type': 'application/json',
            'X-Tab-Id': SekmeIdAl()
          }
        });
        
        if (Yanit.ok) {
          window.location.href = '/';
        } else {
          localStorage.removeItem(AnahtarToken);
          localStorage.removeItem(AnahtarRol);
          localStorage.removeItem(AnahtarKullanici);
          localStorage.removeItem(AnahtarPermissions);
          document.cookie = 'nbt_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        }
      } catch (Hata) {
        localStorage.removeItem(AnahtarToken);
        localStorage.removeItem(AnahtarRol);
        localStorage.removeItem(AnahtarKullanici);
        localStorage.removeItem(AnahtarPermissions);
        document.cookie = 'nbt_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
      }
    }
    
    TokenKontrol();

    document.getElementById('btnLogin').addEventListener('click', GirisYap);
    document.getElementById('loginForm').addEventListener('submit', (Olay) => { Olay.preventDefault(); GirisYap(); });
  </script>
  <script src="/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
