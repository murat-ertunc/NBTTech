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
  <title><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/assets/bootstrap.min.css" />
</head>
<body class="bg-white">
  <nav class="navbar navbar-light bg-white border-bottom shadow-sm">
    <div class="container-fluid">
      <div class="d-flex align-items-center gap-2">
        <img id="brandLogo" src="<?= htmlspecialchars($Logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="height:32px;" />
        <span class="fw-semibold" id="brandName"><?= htmlspecialchars($UygulamaAdi, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button id="usersNav" class="btn btn-outline-primary btn-sm d-none">Kullanıcılar</button>
        <button id="logoutNav" class="btn btn-outline-secondary btn-sm">Çıkış</button>
      </div>
    </div>
  </nav>

  <div class="container-fluid py-3">
    <div class="row g-3">
      <div class="col-lg-3">
        <div class="card shadow-sm h-100">
          <div class="card-body d-flex flex-column gap-3">
            <div class="d-flex align-items-center gap-2">
              <h6 class="mb-0">Müşteriler</h6>
              <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#addModal">Yeni</button>
            </div>
            <input id="search" class="form-control" placeholder="Ara..." />
            <div class="list-group" id="customerList" style="max-height:60vh; overflow:auto;"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-9">
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="alert alert-danger d-none" role="alert" id="alertBox"></div>
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="mb-0" id="selectedTitle">Seçili müşteri yok</h5>
            </div>

            <div class="card border-0 bg-light mb-3">
              <div class="card-body">
                <form class="row g-3" id="detailForm">
                  <div class="col-md-6">
                    <label class="form-label">Unvan</label>
                    <input id="detailUnvan" class="form-control" />
                    <small class="text-muted">En az 2 karakter olmalı.</small>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Açıklama</label>
                    <input id="detailAciklama" class="form-control" />
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <button id="btnSave" class="btn btn-success" type="submit">Kaydet</button>
                  </div>
                </form>
              </div>
            </div>

            <hr />
            <ul class="nav nav-pills mb-3" id="tabs">
              <li class="nav-item"><button class="nav-link active" data-tab="Genel" type="button">Genel</button></li>
              <li class="nav-item"><button class="nav-link" data-tab="Projeler" type="button">Projeler</button></li>
              <li class="nav-item"><button class="nav-link" data-tab="Teklifler" type="button">Teklifler</button></li>
              <li class="nav-item"><button class="nav-link" data-tab="Sozlesmeler" type="button">Sözleşmeler</button></li>
              <li class="nav-item"><button class="nav-link" data-tab="Faturalar" type="button">Faturalar</button></li>
              <li class="nav-item"><button class="nav-link" data-tab="Odemeler" type="button">Ödemeler</button></li>
              <li class="nav-item"><button class="nav-link" data-tab="Teminat" type="button">Teminat/Evrak</button></li>
            </ul>
            <div class="border rounded p-3 bg-light" id="tabContent">Seçili müşteri yok.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Kullanıcılar Modal -->
  <div class="modal fade" id="usersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Kullanıcı Yönetimi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="usersModalError"></div>
          <div class="table-responsive">
            <table class="table table-hover" id="usersTable">
              <thead>
                <tr>
                  <th>Ad Soyad</th>
                  <th>Kullanıcı Adı</th>
                  <th>Rol</th>
                  <th>Durum</th>
                  <th>İşlemler</th>
                </tr>
              </thead>
              <tbody id="usersTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Yeni Müşteri</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-none" role="alert" id="modalError"></div>
          <div class="mb-3">
            <label class="form-label">Unvan</label>
            <input id="addUnvan" class="form-control" />
            <small class="text-muted">En az 2 karakter olmalı.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Açıklama</label>
            <input id="addAciklama" class="form-control" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
          <button type="button" class="btn btn-primary" id="btnAdd">Ekle</button>
        </div>
      </div>
    </div>
  </div>

  <script src="/assets/bootstrap.bundle.min.js"></script>
  <script>
    const UygulamaAyar = <?= json_encode(['name' => $UygulamaAdi, 'logo' => $Logo], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const ApiTabani = '';
    const ListeEl = document.getElementById('customerList');
    const AnahtarToken = 'nbt_token';
    const AnahtarRol = 'nbt_role';
    const AnahtarKullanici = 'nbt_user';
    const AnahtarSekme = 'nbt_tab_id';
    const AramaEl = document.getElementById('search');
    const MusteriEkleModal = new bootstrap.Modal(document.getElementById('addModal'));
    const UyariKutu = document.getElementById('alertBox');
    const ModalHata = document.getElementById('modalError');

    document.title = UygulamaAyar.name || 'NbtProject';
    document.getElementById('brandName').textContent = UygulamaAyar.name || 'NbtProject';
    document.getElementById('brandLogo').src = UygulamaAyar.logo || '/assets/logo.png';

    function SekmeIdAl() {
      let Deger = localStorage.getItem(AnahtarSekme);
      if (!Deger) {
        Deger = crypto.randomUUID();
        localStorage.setItem(AnahtarSekme, Deger);
      }
      return Deger;
    }

    function UyariSinifAyarla(Tip) {
      UyariKutu.classList.remove('alert-success', 'alert-danger');
      UyariKutu.classList.add(Tip === 'success' ? 'alert-success' : 'alert-danger');
    }

    function HataGoster(Mesaj) {
      UyariSinifAyarla('danger');
      UyariKutu.textContent = Mesaj;
      UyariKutu.classList.remove('d-none');
    }

    function BasariGoster(Mesaj) {
      UyariSinifAyarla('success');
      UyariKutu.textContent = Mesaj;
      UyariKutu.classList.remove('d-none');
    }

    function HataTemizle() {
      UyariKutu.classList.add('d-none');
      UyariKutu.textContent = '';
    }

    function ModalHataGoster(Mesaj) {
      ModalHata.textContent = Mesaj;
      ModalHata.classList.remove('d-none');
    }

    function ModalHataTemizle() {
      ModalHata.classList.add('d-none');
      ModalHata.textContent = '';
    }

    function TokenAl() { return localStorage.getItem(AnahtarToken); }
    function RolAl() { return localStorage.getItem(AnahtarRol) || 'user'; }

    // XSS saldirilarina karsi HTML escape
    function HtmlKacis(Deger) {
      if (Deger == null) return '';
      const Eleman = document.createElement('div');
      Eleman.textContent = String(Deger);
      return Eleman.innerHTML;
    }

    if (!TokenAl()) { window.location.href = '/login'; }

    async function Istek(Yol, Ayarlar = {}) {
      const Basliklar = Ayarlar.headers || {};
      if (!Basliklar['Content-Type'] && !(Ayarlar.body instanceof FormData)) {
        Basliklar['Content-Type'] = 'application/json';
      }
      const Tk = TokenAl();
      if (Tk) Basliklar['Authorization'] = 'Bearer ' + Tk;
      Basliklar['X-Tab-Id'] = SekmeIdAl();
      Basliklar['X-Role'] = RolAl();
      const Yanıt = await fetch(ApiTabani + Yol, { ...Ayarlar, headers: Basliklar });
      const Veri = await Yanıt.json().catch(() => ({}));
      if (!Yanıt.ok) {
        if (Yanıt.status === 401) {
          localStorage.removeItem(AnahtarToken);
          localStorage.removeItem(AnahtarRol);
          localStorage.removeItem(AnahtarKullanici);
          window.location.href = '/login';
        } else if (Yanıt.status === 403) {
          throw new Error(Veri.error || 'Yetkiniz yok');
        } else {
          throw new Error(Veri.error || 'Hata');
        }
      }
      return Veri;
    }

    let Musteriler = [];
    let Secili = null;
    let AktifSekme = 'Genel';

    function ListeCiz() {
      const Sorgu = (AramaEl.value || '').toLowerCase();
      ListeEl.innerHTML = '';
      const Filtreli = Musteriler.filter(Kayit => (Kayit.Unvan || '').toLowerCase().includes(Sorgu));
      if (Filtreli.length === 0) {
        const Bos = document.createElement('div');
        Bos.className = 'text-muted small px-2 py-1';
        Bos.textContent = 'Kayıt bulunamadı';
        ListeEl.appendChild(Bos);
        return;
      }
      Filtreli.forEach(Kayit => {
        const Buton = document.createElement('button');
        Buton.className = 'list-group-item list-group-item-action d-flex flex-column align-items-start' + (Secili && Secili.Id === Kayit.Id ? ' active' : '');
        const EkleyenBilgi = (RolAl() === 'superadmin' || RolAl() === 'admin') && Kayit.EkleyenAdSoyad 
          ? `<small class="text-muted">${HtmlKacis(Kayit.EkleyenAdSoyad)}</small>` 
          : '';
        Buton.innerHTML = `<span>${HtmlKacis(Kayit.Unvan)}</span>${EkleyenBilgi}`;
        Buton.addEventListener('click', () => {
          Secili = Kayit;
          SeciliCiz();
          ListeCiz();
        });
        ListeEl.appendChild(Buton);
      });
    }

    async function MusterileriYukle() {
      const OncekiId = Secili ? Secili.Id : null;
      HataTemizle();
      try {
        const Yanit = await Istek('/api/customers');
        Musteriler = Yanit.data || [];
        if (OncekiId) {
          Secili = Musteriler.find(K => K.Id === OncekiId) || (Musteriler.length ? Musteriler[0] : null);
        } else if (Musteriler.length) {
          Secili = Musteriler[0];
        } else {
          Secili = null;
        }
      } catch (Hata) {
        HataGoster(Hata.message);
        Musteriler = [];
        Secili = null;
      }
      ListeCiz();
      SeciliCiz();
    }

    function SeciliCiz() {
      const BaslikEl = document.getElementById('selectedTitle');
      const UnvanEl = document.getElementById('detailUnvan');
      const AciklamaEl = document.getElementById('detailAciklama');
      if (!Secili) {
        BaslikEl.textContent = 'Seçili müşteri yok';
        UnvanEl.value = '';
        AciklamaEl.value = '';
        document.getElementById('tabContent').textContent = 'Seçili müşteri yok.';
        return;
      }
      BaslikEl.textContent = Secili.Unvan;
      UnvanEl.value = Secili.Unvan || '';
      AciklamaEl.value = Secili.Aciklama || '';
      SekmeIcerikGuncelle();
    }

    async function MusteriEkle() {
      const Unvan = document.getElementById('addUnvan').value.trim();
      const Aciklama = document.getElementById('addAciklama').value.trim();
      ModalHataTemizle();
      if (!Unvan) { ModalHataGoster('Ünvan zorunludur.'); return; }
      if (Unvan.length < 2) { ModalHataGoster('Ünvan en az 2 karakter olmalıdır.'); return; }
      try {
        await Istek('/api/customers', { method: 'POST', body: JSON.stringify({ Unvan, Aciklama: Aciklama || null }) });
        document.getElementById('addUnvan').value = '';
        document.getElementById('addAciklama').value = '';
        MusteriEkleModal.hide();
        await MusterileriYukle();
      } catch (Hata) {
        ModalHataGoster(Hata.message);
      }
    }

    async function MusteriKaydet(Olay) {
      Olay.preventDefault();
      if (!Secili) return;
      const Unvan = document.getElementById('detailUnvan').value.trim();
      const Aciklama = document.getElementById('detailAciklama').value.trim();
      HataTemizle();
      if (!Unvan) { HataGoster('Ünvan zorunludur.'); return; }
      if (Unvan.length < 2) { HataGoster('Ünvan en az 2 karakter olmalıdır.'); return; }
      try {
        await Istek(`/api/customers/${Secili.Id}`, { method: 'PUT', body: JSON.stringify({ Unvan, Aciklama: Aciklama || null }) });
        BasariGoster('Düzenleme başarılı.');
        await MusterileriYukle();
      } catch (Hata) {
        HataGoster(Hata.message);
      }
    }

    function SekmeIcerikGuncelle() {
      const Harita = {
        'Genel': `Genel bilgiler: ${Secili ? Secili.Unvan : ''}`,
        'Projeler': 'Projeler sekmesi: içerik eklenebilir.',
        'Teklifler': 'Teklifler sekmesi: içerik eklenebilir.',
        'Sozlesmeler': 'Sözleşmeler sekmesi: içerik eklenebilir.',
        'Faturalar': 'Faturalar sekmesi: içerik eklenebilir.',
        'Odemeler': 'Ödemeler sekmesi: içerik eklenebilir.',
        'Teminat': 'Teminat/Evrak sekmesi: içerik eklenebilir.'
      };
      document.getElementById('tabContent').textContent = Harita[AktifSekme] || '';
    }

    document.getElementById('btnAdd').addEventListener('click', MusteriEkle);
    document.getElementById('detailForm').addEventListener('submit', MusteriKaydet);
    document.getElementById('logoutNav').addEventListener('click', () => {
      localStorage.removeItem(AnahtarToken);
      localStorage.removeItem(AnahtarRol);
      localStorage.removeItem(AnahtarKullanici);
      window.location.href = '/login';
    });
    AramaEl.addEventListener('input', ListeCiz);

    document.querySelectorAll('#tabs button').forEach(Dugme => {
      Dugme.addEventListener('click', () => {
        document.querySelectorAll('#tabs button').forEach(b => b.classList.remove('active'));
        Dugme.classList.add('active');
        AktifSekme = Dugme.getAttribute('data-tab');
        SekmeIcerikGuncelle();
      });
    });

    MusterileriYukle().catch(Hata => HataGoster(Hata.message));

    // Kullanici yonetimi (sadece superadmin)
    const KullaniciModal = new bootstrap.Modal(document.getElementById('usersModal'));
    const KullaniciTabloGovde = document.getElementById('usersTableBody');
    const KullaniciModalHata = document.getElementById('usersModalError');

    function KullaniciModalHataGoster(Mesaj) {
      KullaniciModalHata.textContent = Mesaj;
      KullaniciModalHata.classList.remove('d-none');
    }
    function KullaniciModalHataTemizle() {
      KullaniciModalHata.classList.add('d-none');
      KullaniciModalHata.textContent = '';
    }

    if (RolAl() === 'superadmin') {
      document.getElementById('usersNav').classList.remove('d-none');
    }

    async function KullanicilariYukle() {
      KullaniciModalHataTemizle();
      try {
        const Yanit = await Istek('/api/users');
        const Kullanicilar = Yanit.data || [];
        KullaniciTabloGovde.innerHTML = '';
        Kullanicilar.forEach(K => {
          const Satir = document.createElement('tr');
          const AktifMi = K.Aktif == 1 || K.Aktif === '1';
          const DurumRozet = AktifMi 
            ? '<span class="badge bg-success">Aktif</span>' 
            : '<span class="badge bg-danger">Bloklu</span>';
          const SuperadminMi = K.Rol === 'superadmin';
          let Islemler = '';
          if (!SuperadminMi) {
            const BlokDugme = AktifMi
              ? `<button class="btn btn-sm btn-outline-warning me-1" onclick="KullaniciBlokDegistir(${K.Id}, 0)">Blokla</button>`
              : `<button class="btn btn-sm btn-outline-success me-1" onclick="KullaniciBlokDegistir(${K.Id}, 1)">Aktifle</button>`;
            Islemler = `${BlokDugme}<button class="btn btn-sm btn-outline-danger" onclick="KullaniciSil(${K.Id})">Sil</button>`;
          }
          Satir.innerHTML = `
            <td>${HtmlKacis(K.AdSoyad || '')}</td>
            <td>${HtmlKacis(K.KullaniciAdi || '')}</td>
            <td>${HtmlKacis(K.Rol || '')}</td>
            <td>${DurumRozet}</td>
            <td>${Islemler}</td>
          `;
          KullaniciTabloGovde.appendChild(Satir);
        });
      } catch (Hata) {
        KullaniciModalHataGoster(Hata.message);
      }
    }

    window.KullaniciBlokDegistir = async function(Id, Aktif) {
      const IslemMetni = Aktif === 1 ? 'aktif etmek' : 'bloklamak';
      if (!confirm(`Bu kullanıcıyı ${IslemMetni} istediğinize emin misiniz?`)) return;
      KullaniciModalHataTemizle();
      try {
        await Istek(`/api/users/${Id}/block`, { method: 'PUT', body: JSON.stringify({ Aktif }) });
        await KullanicilariYukle();
      } catch (Hata) {
        KullaniciModalHataGoster(Hata.message);
      }
    };

    window.KullaniciSil = async function(Id) {
      if (!confirm('Bu kullanıcıyı kalıcı olarak silmek istediğinize emin misiniz?')) return;
      KullaniciModalHataTemizle();
      try {
        await Istek(`/api/users/${Id}`, { method: 'DELETE' });
        await KullanicilariYukle();
      } catch (Hata) {
        KullaniciModalHataGoster(Hata.message);
      }
    };

    document.getElementById('usersNav').addEventListener('click', () => {
      KullanicilariYukle();
      KullaniciModal.show();
    });
  </script>
</body>
</html>
