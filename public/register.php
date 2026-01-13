<?php
require __DIR__ . '/../app/Core/bootstrap.php';

$UygulamaAdi = config('app.name', 'NbtProject');
$Logo = config('app.logo', '/assets/logo.png');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?> | Kayıt</title>
  <link rel="stylesheet" href="/assets/bootstrap.min.css" />
</head>
<body class="bg-light">
  <div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-sm p-4" style="max-width:420px; width:100%;">
      <form id="registerForm" novalidate>
        <div class="text-center mb-3">
          <img id="brandLogo" src="<?= htmlspecialchars($Logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-height:80px;" class="mb-2" />
          <h5 class="mb-0" id="brandName"><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></h5>
          <small class="text-muted">Kayıt olun</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Ad Soyad</label>
          <input id="name" class="form-control" placeholder="Ad Soyad" />
          <small class="text-muted">En az 2 karakter olmalı.</small>
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
          <button id="btnRegister" class="btn btn-primary" type="submit">Kayıt Ol</button>
          <button id="btnBack" class="btn btn-outline-secondary" type="button">Girişe dön</button>
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

    document.title = `${UygulamaAyar.name || 'NbtProject'} | Kayıt`;
    document.getElementById('brandName').textContent = UygulamaAyar.name || 'NbtProject';
    document.getElementById('brandLogo').src = UygulamaAyar.logo || '/assets/logo.png';

    function SekmeIdAl() {
      let Deger = localStorage.getItem(AnahtarSekme);
      if (!Deger) { Deger = crypto.randomUUID(); localStorage.setItem(AnahtarSekme, Deger); }
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

    async function KayitOl() {
      try {
        HataTemizle();
        const AdSoyad = document.getElementById('name').value.trim();
        const KullaniciAdi = document.getElementById('username').value.trim();
        const Parola = document.getElementById('password').value.trim();
        if (!AdSoyad) { HataGoster('Ad Soyad zorunludur.'); return; }
        if (AdSoyad.length < 2) { HataGoster('Ad Soyad en az 2 karakter olmalıdır.'); return; }
        if (!KullaniciAdi) { HataGoster('Kullanıcı adı zorunludur.'); return; }
        if (KullaniciAdi.length < 3) { HataGoster('Kullanıcı adı en az 3 karakter olmalıdır.'); return; }
        if (!Parola) { HataGoster('Parola zorunludur.'); return; }
        if (Parola.length < 6) { HataGoster('Parola en az 6 karakter olmalıdır.'); return; }
        const Yanıt = await fetch('/api/register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Tab-Id': SekmeIdAl() },
          body: JSON.stringify({ name: AdSoyad, username: KullaniciAdi, password: Parola })
        });
        const Veri = await Yanıt.json().catch(() => ({}));
        if (!Yanıt.ok) throw new Error(Veri.error || 'Kayıt oluşturulamadı.');

        // Kayıt sonrası auth garantisi: login çağrısı ile taze token alma
        const GirisYaniti = await fetch('/api/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Tab-Id': SekmeIdAl() },
          body: JSON.stringify({ username: KullaniciAdi, password: Parola })
        });
        const GirisVeri = await GirisYaniti.json().catch(() => ({}));
        const Sonuc = GirisYaniti.ok ? GirisVeri : Veri; // login başarısızsa register token'ını kullan

        localStorage.setItem(AnahtarToken, Sonuc.token);
        if (Sonuc.user?.role) localStorage.setItem(AnahtarRol, Sonuc.user.role);
        if (Sonuc.user) localStorage.setItem(AnahtarKullanici, JSON.stringify(Sonuc.user));
        window.location.href = '/';
      } catch (Hata) {
        HataGoster(Hata.message);
      }
    }

    if (localStorage.getItem(AnahtarToken)) {
      window.location.href = '/';
    }

    document.getElementById('btnRegister').addEventListener('click', KayitOl);
    document.getElementById('registerForm').addEventListener('submit', (Olay) => { Olay.preventDefault(); KayitOl(); });
    document.getElementById('btnBack').addEventListener('click', () => { window.location.href = '/login'; });
  </script>
  <script src="/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
