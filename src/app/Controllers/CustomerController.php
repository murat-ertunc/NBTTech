<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\CustomerRepository;
use App\Services\Authorization\AuthorizationService;
use App\Services\Logger\ActionLogger;

class CustomerController
{
    public static function index(): void
    {
        $Repo = new CustomerRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        
        // Permission bazli scope kontrolu
        $AuthService = AuthorizationService::getInstance();
        $TumunuGorebilir = $AuthService->tumunuGorebilirMi($KullaniciId, 'customers');
        
        // Pagination parametreleri
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);
        $SayfalamaAktif = isset($_GET['page']) || isset($_GET['limit']);
        
        // Arama parametresi
        $Arama = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        
        if ($TumunuGorebilir) {
            // customers.read_all yetkisi var - tum musterileri gorsun
            if ($SayfalamaAktif) {
                $Sonuc = $Repo->tumAktiflerSiraliPaginated($Sayfa, $Limit, $Arama);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->tumAktiflerSiraliKullaniciBilgisiIle();
                Response::json(['data' => $Satirlar]);
            }
        } else {
            // Sadece kendi olusturdugu musterileri gorsun
            if ($SayfalamaAktif) {
                $Sonuc = $Repo->kullaniciyaGoreAktiflerPaginated($KullaniciId, $Sayfa, $Limit, $Arama);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->kullaniciyaGoreAktifler($KullaniciId);
                Response::json(['data' => $Satirlar]);
            }
        }
    }

    /**
     * Tek Musteri Detayi Getir
     */
    public static function show(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 404);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new CustomerRepository();
        
        // Permission bazli scope kontrolu
        $AuthService = AuthorizationService::getInstance();
        $TumunuGorebilir = $AuthService->tumunuGorebilirMi($KullaniciId, 'customers');

        $Musteri = $TumunuGorebilir
            ? $Repo->bul($Id)
            : $Repo->sahipliKayitBul($Id, $KullaniciId);

        if (!$Musteri) {
            Response::error('Musteri bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Musteri]);
    }

    // Karakter limitleri
    private const LIMITLER = [
        'MusteriKodu' => 10,
        'Unvan' => 150,
        'VergiDairesi' => 50,
        'VergiNo' => 11,
        'MersisNo' => 16,
        'Telefon' => 20,
        'Faks' => 20,
        'Web' => 150,
        'Il' => 50,
        'Ilce' => 50,
        'Adres' => 300,
        'Aciklama' => 500,
    ];

    private static function alanDogrula(string $Alan, ?string $Deger): ?string
    {
        if ($Deger === null || $Deger === '') {
            return null;
        }
        $Limit = self::LIMITLER[$Alan] ?? 255;
        if (mb_strlen($Deger) > $Limit) {
            return "$Alan en fazla $Limit karakter olabilir.";
        }
        return null;
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        // Zorunlu alanlar ve hata mesajlari
        $ZorunluAlanlar = [
            'Unvan' => 'Ünvan zorunludur.',
            'VergiDairesi' => 'Vergi Dairesi zorunludur.',
            'VergiNo' => 'Vergi No zorunludur.'
        ];
        
        $Hatalar = [];
        foreach ($ZorunluAlanlar as $Alan => $Mesaj) {
            $Deger = isset($Girdi[$Alan]) ? trim((string) $Girdi[$Alan]) : '';
            if ($Deger === '') {
                $Hatalar[$Alan] = $Mesaj;
            }
        }
        
        // Hatalar varsa 422 ile geri don
        if (!empty($Hatalar)) {
            Response::json(['errors' => $Hatalar, 'message' => 'Lütfen zorunlu alanları doldurun.'], 422);
            return;
        }
        
        $Unvan = trim((string) $Girdi['Unvan']);
        if (mb_strlen($Unvan) < 2) {
            Response::json(['errors' => ['Unvan' => 'Ünvan en az 2 karakter olmalıdır.'], 'message' => 'Ünvan en az 2 karakter olmalıdır.'], 422);
            return;
        }
        if (mb_strlen($Unvan) > self::LIMITLER['Unvan']) {
            Response::json(['errors' => ['Unvan' => 'Ünvan en fazla ' . self::LIMITLER['Unvan'] . ' karakter olabilir.'], 'message' => 'Ünvan çok uzun.'], 422);
            return;
        }
        
        // Vergi No format kontrolu (10 veya 11 hane, sadece rakam)
        $VergiNo = trim((string) $Girdi['VergiNo']);
        if (!preg_match('/^\d{10,11}$/', $VergiNo)) {
            Response::json(['errors' => ['VergiNo' => 'Vergi No 10 veya 11 haneli sayısal olmalıdır.'], 'message' => 'Vergi No formatı geçersiz.'], 422);
            return;
        }

        // Alan validasyonlari
        $Alanlar = ['MusteriKodu', 'VergiDairesi', 'VergiNo', 'MersisNo', 'Telefon', 'Faks', 'Web', 'Il', 'Ilce', 'Adres', 'Aciklama'];
        foreach ($Alanlar as $Alan) {
            $Deger = isset($Girdi[$Alan]) ? trim((string) $Girdi[$Alan]) : null;
            $Hata = self::alanDogrula($Alan, $Deger);
            if ($Hata) {
                Response::json(['errors' => [$Alan => $Hata], 'message' => $Hata], 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        $Repo = new CustomerRepository();
        $Id = Transaction::wrap(function () use ($Repo, $Unvan, $Girdi, $KullaniciId) {
            return $Repo->ekle([
                'Unvan' => $Unvan,
                'Aciklama' => isset($Girdi['Aciklama']) ? mb_substr(trim((string) $Girdi['Aciklama']), 0, 500) : null,
                'MusteriKodu' => isset($Girdi['MusteriKodu']) ? mb_substr(trim((string) $Girdi['MusteriKodu']), 0, 10) : null,
                'VergiDairesi' => isset($Girdi['VergiDairesi']) ? mb_substr(trim((string) $Girdi['VergiDairesi']), 0, 50) : null,
                'VergiNo' => isset($Girdi['VergiNo']) ? mb_substr(trim((string) $Girdi['VergiNo']), 0, 11) : null,
                'Il' => isset($Girdi['Il']) ? mb_substr(trim((string) $Girdi['Il']), 0, 50) : null,
                'Ilce' => isset($Girdi['Ilce']) ? mb_substr(trim((string) $Girdi['Ilce']), 0, 50) : null,
                'Adres' => isset($Girdi['Adres']) ? mb_substr(trim((string) $Girdi['Adres']), 0, 300) : null,
                'Telefon' => isset($Girdi['Telefon']) ? mb_substr(trim((string) $Girdi['Telefon']), 0, 20) : null,
                'Faks' => isset($Girdi['Faks']) ? mb_substr(trim((string) $Girdi['Faks']), 0, 20) : null,
                'MersisNo' => isset($Girdi['MersisNo']) ? mb_substr(trim((string) $Girdi['MersisNo']), 0, 16) : null,
                'Web' => isset($Girdi['Web']) ? mb_substr(trim((string) $Girdi['Web']), 0, 150) : null,
            ], $KullaniciId);
        });

        Response::json(['id' => $Id], 201);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        // Zorunlu alanlar kontrolu (gonderilmisse bos olamaz)
        $ZorunluAlanlar = ['Unvan', 'VergiDairesi', 'VergiNo'];
        $Hatalar = [];
        
        foreach ($ZorunluAlanlar as $Alan) {
            if (array_key_exists($Alan, $Girdi)) {
                $Deger = trim((string) $Girdi[$Alan]);
                if ($Deger === '') {
                    $Hatalar[$Alan] = $Alan . ' zorunludur.';
                }
            }
        }
        
        if (!empty($Hatalar)) {
            Response::json(['errors' => $Hatalar, 'message' => 'Lütfen zorunlu alanları doldurun.'], 422);
            return;
        }
        
        // Unvan validasyonu
        if (isset($Girdi['Unvan'])) {
            $Girdi['Unvan'] = trim((string) $Girdi['Unvan']);
            if (mb_strlen($Girdi['Unvan']) < 2) {
                Response::json(['errors' => ['Unvan' => 'Ünvan en az 2 karakter olmalıdır.'], 'message' => 'Ünvan çok kısa.'], 422);
                return;
            }
            if (mb_strlen($Girdi['Unvan']) > self::LIMITLER['Unvan']) {
                Response::json(['errors' => ['Unvan' => 'Ünvan en fazla ' . self::LIMITLER['Unvan'] . ' karakter olabilir.'], 'message' => 'Ünvan çok uzun.'], 422);
                return;
            }
        }
        
        // Vergi No format kontrolu (gonderilmisse)
        if (isset($Girdi['VergiNo']) && trim($Girdi['VergiNo']) !== '') {
            $VergiNo = trim((string) $Girdi['VergiNo']);
            if (!preg_match('/^\d{10,11}$/', $VergiNo)) {
                Response::json(['errors' => ['VergiNo' => 'Vergi No 10 veya 11 haneli sayısal olmalıdır.'], 'message' => 'Vergi No formatı geçersiz.'], 422);
                return;
            }
        }

        // Alan validasyonlari
        $Alanlar = ['MusteriKodu', 'VergiDairesi', 'VergiNo', 'MersisNo', 'Telefon', 'Faks', 'Web', 'Il', 'Ilce', 'Adres', 'Aciklama'];
        foreach ($Alanlar as $Alan) {
            if (array_key_exists($Alan, $Girdi)) {
                $Deger = trim((string) $Girdi[$Alan]);
                $Hata = self::alanDogrula($Alan, $Deger);
                if ($Hata) {
                    Response::json(['errors' => [$Alan => $Hata], 'message' => $Hata], 422);
                    return;
                }
            }
        }

        $Repo = new CustomerRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        
        // Permission bazli scope kontrolu
        $AuthService = AuthorizationService::getInstance();
        $TumunuDuzenleyebilir = $AuthService->tumunuDuzenleyebilirMi($KullaniciId, 'customers');
        
        $Mevcut = $TumunuDuzenleyebilir
            ? $Repo->bul($Id)
            : $Repo->sahipliKayitBul($Id, $KullaniciId);
        if (!$Mevcut) {
            Response::error('Musteri bulunamadi.', 404);
            return;
        }
        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId, $TumunuDuzenleyebilir, $Mevcut) {
            $GuncellenecekVeri = [];
            if (isset($Girdi['Unvan'])) {
                $GuncellenecekVeri['Unvan'] = mb_substr(trim((string) $Girdi['Unvan']), 0, 150);
            }
            if (array_key_exists('Aciklama', $Girdi)) {
                $GuncellenecekVeri['Aciklama'] = $Girdi['Aciklama'] ? mb_substr(trim((string) $Girdi['Aciklama']), 0, 500) : null;
            }
            if (array_key_exists('MusteriKodu', $Girdi)) {
                $GuncellenecekVeri['MusteriKodu'] = $Girdi['MusteriKodu'] ? mb_substr(trim((string) $Girdi['MusteriKodu']), 0, 10) : null;
            }
            if (array_key_exists('VergiDairesi', $Girdi)) {
                $GuncellenecekVeri['VergiDairesi'] = $Girdi['VergiDairesi'] ? mb_substr(trim((string) $Girdi['VergiDairesi']), 0, 50) : null;
            }
            if (array_key_exists('VergiNo', $Girdi)) {
                $GuncellenecekVeri['VergiNo'] = $Girdi['VergiNo'] ? mb_substr(trim((string) $Girdi['VergiNo']), 0, 11) : null;
            }
            if (array_key_exists('Il', $Girdi)) {
                $GuncellenecekVeri['Il'] = $Girdi['Il'] ? mb_substr(trim((string) $Girdi['Il']), 0, 50) : null;
            }
            if (array_key_exists('Ilce', $Girdi)) {
                $GuncellenecekVeri['Ilce'] = $Girdi['Ilce'] ? mb_substr(trim((string) $Girdi['Ilce']), 0, 50) : null;
            }
            if (array_key_exists('Adres', $Girdi)) {
                $GuncellenecekVeri['Adres'] = $Girdi['Adres'] ? mb_substr(trim((string) $Girdi['Adres']), 0, 300) : null;
            }
            if (array_key_exists('Telefon', $Girdi)) {
                $GuncellenecekVeri['Telefon'] = $Girdi['Telefon'] ? mb_substr(trim((string) $Girdi['Telefon']), 0, 20) : null;
            }
            if (array_key_exists('Faks', $Girdi)) {
                $GuncellenecekVeri['Faks'] = $Girdi['Faks'] ? mb_substr(trim((string) $Girdi['Faks']), 0, 20) : null;
            }
            if (array_key_exists('MersisNo', $Girdi)) {
                $GuncellenecekVeri['MersisNo'] = $Girdi['MersisNo'] ? mb_substr(trim((string) $Girdi['MersisNo']), 0, 16) : null;
            }
            if (array_key_exists('Web', $Girdi)) {
                $GuncellenecekVeri['Web'] = $Girdi['Web'] ? mb_substr(trim((string) $Girdi['Web']), 0, 150) : null;
            }
            if (!empty($GuncellenecekVeri)) {
                $Repo->guncelle($Id, $GuncellenecekVeri, $KullaniciId, $TumunuDuzenleyebilir ? [] : ['EkleyenUserId' => $KullaniciId]);
            }
        });

        Response::json(['status' => 'ok']);
    }

    /**
     * Musteri cari ozeti - yil ve doviz bazli fatura toplamlari
     */
    public static function cariOzet(array $Parametreler): void
    {
        $MusteriId = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($MusteriId <= 0) {
            Response::error('Gecersiz musteri ID.', 422);
            return;
        }
        
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        
        $InvoiceRepo = new \App\Repositories\InvoiceRepository();
        $Ozet = $InvoiceRepo->cariOzet($MusteriId);
        
        Response::json(['data' => $Ozet]);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }
        $Repo = new CustomerRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        
        // Permission bazli scope kontrolu
        $AuthService = AuthorizationService::getInstance();
        $TumunuDuzenleyebilir = $AuthService->tumunuDuzenleyebilirMi($KullaniciId, 'customers');
        
        $Mevcut = $TumunuDuzenleyebilir
            ? $Repo->bul($Id)
            : $Repo->sahipliKayitBul($Id, $KullaniciId);
        if (!$Mevcut) {
            Response::error('Musteri bulunamadi.', 404);
            return;
        }
        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId, $TumunuDuzenleyebilir, $Mevcut) {
            $Repo->yedekle($Id, 'bck_tbl_musteri', $KullaniciId);
            $Repo->softSil($Id, $KullaniciId, $TumunuDuzenleyebilir ? [] : ['EkleyenUserId' => $KullaniciId]);
            ActionLogger::delete('tbl_musteri', ['Id' => $Id, 'EkleyenUserId' => $Mevcut['EkleyenUserId'] ?? $KullaniciId]);
        });

        Response::json(['status' => 'ok']);
    }
}
