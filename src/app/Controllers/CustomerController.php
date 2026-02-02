<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\CustomerRepository;
use App\Repositories\CityRepository;
use App\Repositories\DistrictRepository;
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
        
        
        $AuthService = AuthorizationService::getInstance();
        $TumunuGorebilir = $AuthService->tumunuGorebilirMi($KullaniciId, 'customers');
        
        
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);
        $SayfalamaAktif = isset($_GET['page']) || isset($_GET['limit']);
        
        
        $Arama = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        
        if ($TumunuGorebilir) {
            
            if ($SayfalamaAktif) {
                $Sonuc = $Repo->tumAktiflerSiraliPaginated($Sayfa, $Limit, $Arama);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->tumAktiflerSiraliKullaniciBilgisiIle();
                Response::json(['data' => $Satirlar]);
            }
        } else {
            
            if ($SayfalamaAktif) {
                $Sonuc = $Repo->kullaniciyaGoreAktiflerPaginated($KullaniciId, $Sayfa, $Limit, $Arama);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->kullaniciyaGoreAktifler($KullaniciId);
                Response::json(['data' => $Satirlar]);
            }
        }
    }

    


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
        'SehirId' => null, 
        'IlceId' => null,  
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

    private static function sehirIlceDogrula(array $Girdi): array
    {
        $SehirId = null;
        if (array_key_exists('SehirId', $Girdi)) {
            $SehirRaw = $Girdi['SehirId'];
            if ($SehirRaw !== null && $SehirRaw !== '' && (int)$SehirRaw > 0) {
                $SehirId = (int)$SehirRaw;
            }
        }

        $IlceId = null;
        if (array_key_exists('IlceId', $Girdi)) {
            $IlceRaw = $Girdi['IlceId'];
            if ($IlceRaw !== null && $IlceRaw !== '' && (int)$IlceRaw > 0) {
                $IlceId = (int)$IlceRaw;
            }
        }

        if ($IlceId !== null && $SehirId === null) {
            return ['ok' => false, 'message' => 'Ilce secildiyse SehirId zorunludur.'];
        }

        $SehirAdi = null;
        $IlceAdi = null;

        if ($SehirId !== null) {
            $SehirRepo = new CityRepository();
            $Sehir = $SehirRepo->bul($SehirId);
            if (!$Sehir) {
                return ['ok' => false, 'message' => 'Gecersiz sehir secimi.'];
            }
            $SehirAdi = $Sehir['Ad'] ?? null;
        }

        if ($IlceId !== null) {
            $IlceRepo = new DistrictRepository();
            $Ilce = $IlceRepo->bul($IlceId);
            if (!$Ilce) {
                return ['ok' => false, 'message' => 'Gecersiz ilce secimi.'];
            }
            if ($SehirId !== null && (int)$Ilce['SehirId'] !== $SehirId) {
                return ['ok' => false, 'message' => 'Ilce, secilen sehre ait degil.'];
            }
            $IlceAdi = $Ilce['Ad'] ?? null;
        }

        return [
            'ok' => true,
            'SehirId' => $SehirId,
            'IlceId' => $IlceId,
            'Il' => $SehirAdi,
            'Ilce' => $IlceAdi,
        ];
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        
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
        
        
        $VergiNo = trim((string) $Girdi['VergiNo']);
        if (!preg_match('/^\d{10,11}$/', $VergiNo)) {
            Response::json(['errors' => ['VergiNo' => 'Vergi No 10 veya 11 haneli sayısal olmalıdır.'], 'message' => 'Vergi No formatı geçersiz.'], 422);
            return;
        }

        
        $Alanlar = ['MusteriKodu', 'VergiDairesi', 'VergiNo', 'MersisNo', 'Telefon', 'Faks', 'Web', 'Il', 'Ilce', 'Adres', 'Aciklama'];
        foreach ($Alanlar as $Alan) {
            $Deger = isset($Girdi[$Alan]) ? trim((string) $Girdi[$Alan]) : null;
            $Hata = self::alanDogrula($Alan, $Deger);
            if ($Hata) {
                Response::json(['errors' => [$Alan => $Hata], 'message' => $Hata], 422);
                return;
            }
        }

        $SehirIlce = self::sehirIlceDogrula($Girdi);
        if (!$SehirIlce['ok']) {
            Response::json(['errors' => ['SehirId' => $SehirIlce['message']], 'message' => $SehirIlce['message']], 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        $Repo = new CustomerRepository();
        $Id = Transaction::wrap(function () use ($Repo, $Unvan, $Girdi, $KullaniciId, $SehirIlce) {
            return $Repo->ekle([
                'Unvan' => $Unvan,
                'Aciklama' => isset($Girdi['Aciklama']) ? mb_substr(trim((string) $Girdi['Aciklama']), 0, 500) : null,
                'MusteriKodu' => isset($Girdi['MusteriKodu']) ? mb_substr(trim((string) $Girdi['MusteriKodu']), 0, 10) : null,
                'VergiDairesi' => isset($Girdi['VergiDairesi']) ? mb_substr(trim((string) $Girdi['VergiDairesi']), 0, 50) : null,
                'VergiNo' => isset($Girdi['VergiNo']) ? mb_substr(trim((string) $Girdi['VergiNo']), 0, 11) : null,
                'Il' => $SehirIlce['Il'] ?? (isset($Girdi['Il']) ? mb_substr(trim((string) $Girdi['Il']), 0, 50) : null),
                'Ilce' => $SehirIlce['Ilce'] ?? (isset($Girdi['Ilce']) ? mb_substr(trim((string) $Girdi['Ilce']), 0, 50) : null),
                'Adres' => isset($Girdi['Adres']) ? mb_substr(trim((string) $Girdi['Adres']), 0, 300) : null,
                'Telefon' => isset($Girdi['Telefon']) ? mb_substr(trim((string) $Girdi['Telefon']), 0, 20) : null,
                'Faks' => isset($Girdi['Faks']) ? mb_substr(trim((string) $Girdi['Faks']), 0, 20) : null,
                'MersisNo' => isset($Girdi['MersisNo']) ? mb_substr(trim((string) $Girdi['MersisNo']), 0, 16) : null,
                'Web' => isset($Girdi['Web']) ? mb_substr(trim((string) $Girdi['Web']), 0, 150) : null,
                'SehirId' => $SehirIlce['SehirId'],
                'IlceId' => $SehirIlce['IlceId'],
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
        
        
        if (isset($Girdi['VergiNo']) && trim($Girdi['VergiNo']) !== '') {
            $VergiNo = trim((string) $Girdi['VergiNo']);
            if (!preg_match('/^\d{10,11}$/', $VergiNo)) {
                Response::json(['errors' => ['VergiNo' => 'Vergi No 10 veya 11 haneli sayısal olmalıdır.'], 'message' => 'Vergi No formatı geçersiz.'], 422);
                return;
            }
        }

        
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
        
        
        $AuthService = AuthorizationService::getInstance();
        $TumunuDuzenleyebilir = $AuthService->tumunuDuzenleyebilirMi($KullaniciId, 'customers');
        
        $Mevcut = $TumunuDuzenleyebilir
            ? $Repo->bul($Id)
            : $Repo->sahipliKayitBul($Id, $KullaniciId);
        if (!$Mevcut) {
            Response::error('Musteri bulunamadi.', 404);
            return;
        }

        $SehirIlce = self::sehirIlceDogrula($Girdi);
        if (!$SehirIlce['ok']) {
            Response::json(['errors' => ['SehirId' => $SehirIlce['message']], 'message' => $SehirIlce['message']], 422);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId, $TumunuDuzenleyebilir, $Mevcut, $SehirIlce) {
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
            if (array_key_exists('SehirId', $Girdi)) {
                $GuncellenecekVeri['SehirId'] = $SehirIlce['SehirId'];
                $GuncellenecekVeri['Il'] = $SehirIlce['Il'];
            }
            if (array_key_exists('IlceId', $Girdi)) {
                $GuncellenecekVeri['IlceId'] = $SehirIlce['IlceId'];
                $GuncellenecekVeri['Ilce'] = $SehirIlce['Ilce'];
            }
            if (!empty($GuncellenecekVeri)) {
                $Repo->guncelle($Id, $GuncellenecekVeri, $KullaniciId, $TumunuDuzenleyebilir ? [] : ['EkleyenUserId' => $KullaniciId]);
            }
        });

        Response::json(['status' => 'ok']);
    }

    


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
