<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\CustomerRepository;
use App\Services\Logger\ActionLogger;

class CustomerController
{
    public static function index(): void
    {
        $Repo = new CustomerRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        $Rol = Context::rol();
        
        // Pagination parametreleri
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);
        $usePagination = isset($_GET['page']) || isset($_GET['limit']);
        
        if ($Rol === 'superadmin') {
            // Superadmin ve admin tüm müşterileri ekleyen kullanıcı bilgisiyle görsün
            if ($usePagination) {
                $result = $Repo->tumAktiflerSiraliPaginated($page, $limit);
                Response::json($result);
            } else {
                $Satirlar = $Repo->tumAktiflerSiraliKullaniciBilgisiIle();
                Response::json(['data' => $Satirlar]);
            }
        } else {
            if ($usePagination) {
                $result = $Repo->kullaniciyaGoreAktiflerPaginated($KullaniciId, $page, $limit);
                Response::json($result);
            } else {
                $Satirlar = $Repo->kullaniciyaGoreAktifler($KullaniciId);
                Response::json(['data' => $Satirlar]);
            }
        }
    }

    // Karakter limitleri
    private const LIMITLER = [
        'MusteriKodu' => 5,
        'Unvan' => 150,
        'VergiDairesi' => 50,
        'VergiNo' => 11,
        'MersisNo' => 16,
        'Telefon' => 20,
        'Faks' => 20,
        'Web' => 150,
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
        $Zorunlu = ['Unvan'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error('Ünvan zorunludur.', 422);
                return;
            }
        }
        $Unvan = trim((string) $Girdi['Unvan']);
        if (mb_strlen($Unvan) < 2) {
            Response::error('Ünvan en az 2 karakter olmalıdır.', 422);
            return;
        }
        if (mb_strlen($Unvan) > self::LIMITLER['Unvan']) {
            Response::error('Ünvan en fazla ' . self::LIMITLER['Unvan'] . ' karakter olabilir.', 422);
            return;
        }

        // Alan validasyonları
        $Alanlar = ['MusteriKodu', 'VergiDairesi', 'VergiNo', 'MersisNo', 'Telefon', 'Faks', 'Web', 'Adres', 'Aciklama'];
        foreach ($Alanlar as $Alan) {
            $Deger = isset($Girdi[$Alan]) ? trim((string) $Girdi[$Alan]) : null;
            $Hata = self::alanDogrula($Alan, $Deger);
            if ($Hata) {
                Response::error($Hata, 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        $Repo = new CustomerRepository();
        $Id = Transaction::wrap(function () use ($Repo, $Unvan, $Girdi, $KullaniciId) {
            return $Repo->ekle([
                'Unvan' => $Unvan,
                'Aciklama' => isset($Girdi['Aciklama']) ? mb_substr(trim((string) $Girdi['Aciklama']), 0, 500) : null,
                'MusteriKodu' => isset($Girdi['MusteriKodu']) ? mb_substr(trim((string) $Girdi['MusteriKodu']), 0, 5) : null,
                'VergiDairesi' => isset($Girdi['VergiDairesi']) ? mb_substr(trim((string) $Girdi['VergiDairesi']), 0, 50) : null,
                'VergiNo' => isset($Girdi['VergiNo']) ? mb_substr(trim((string) $Girdi['VergiNo']), 0, 11) : null,
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
            Response::error('Geçersiz kayıt.', 422);
            return;
        }
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        if (isset($Girdi['Unvan'])) {
            $Girdi['Unvan'] = trim((string) $Girdi['Unvan']);
            if ($Girdi['Unvan'] === '') {
                Response::error('Ünvan zorunludur.', 422);
                return;
            }
            if (mb_strlen($Girdi['Unvan']) < 2) {
                Response::error('Ünvan en az 2 karakter olmalıdır.', 422);
                return;
            }
            if (mb_strlen($Girdi['Unvan']) > self::LIMITLER['Unvan']) {
                Response::error('Ünvan en fazla ' . self::LIMITLER['Unvan'] . ' karakter olabilir.', 422);
                return;
            }
        }

        // Alan validasyonları
        $Alanlar = ['MusteriKodu', 'VergiDairesi', 'VergiNo', 'MersisNo', 'Telefon', 'Faks', 'Web', 'Adres', 'Aciklama'];
        foreach ($Alanlar as $Alan) {
            if (array_key_exists($Alan, $Girdi)) {
                $Deger = trim((string) $Girdi[$Alan]);
                $Hata = self::alanDogrula($Alan, $Deger);
                if ($Hata) {
                    Response::error($Hata, 422);
                    return;
                }
            }
        }

        $Repo = new CustomerRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        $Rol = Context::rol();
        $Mevcut = $Rol === 'superadmin'
            ? $Repo->bul($Id)
            : $Repo->sahipliKayitBul($Id, $KullaniciId);
        if (!$Mevcut) {
            Response::error('Müşteri bulunamadı.', 404);
            return;
        }
        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId, $Rol, $Mevcut) {
            $GuncellenecekVeri = [];
            if (isset($Girdi['Unvan'])) {
                $GuncellenecekVeri['Unvan'] = mb_substr(trim((string) $Girdi['Unvan']), 0, 150);
            }
            if (array_key_exists('Aciklama', $Girdi)) {
                $GuncellenecekVeri['Aciklama'] = $Girdi['Aciklama'] ? mb_substr(trim((string) $Girdi['Aciklama']), 0, 500) : null;
            }
            if (array_key_exists('MusteriKodu', $Girdi)) {
                $GuncellenecekVeri['MusteriKodu'] = $Girdi['MusteriKodu'] ? mb_substr(trim((string) $Girdi['MusteriKodu']), 0, 5) : null;
            }
            if (array_key_exists('VergiDairesi', $Girdi)) {
                $GuncellenecekVeri['VergiDairesi'] = $Girdi['VergiDairesi'] ? mb_substr(trim((string) $Girdi['VergiDairesi']), 0, 50) : null;
            }
            if (array_key_exists('VergiNo', $Girdi)) {
                $GuncellenecekVeri['VergiNo'] = $Girdi['VergiNo'] ? mb_substr(trim((string) $Girdi['VergiNo']), 0, 11) : null;
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
                $Repo->guncelle($Id, $GuncellenecekVeri, $KullaniciId, $Rol === 'superadmin' ? [] : ['EkleyenUserId' => $KullaniciId]);
            }
        });

        Response::json(['status' => 'ok']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }
        $Repo = new CustomerRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        $Rol = Context::rol();
        $Mevcut = $Rol === 'superadmin'
            ? $Repo->bul($Id)
            : $Repo->sahipliKayitBul($Id, $KullaniciId);
        if (!$Mevcut) {
            Response::error('Müşteri bulunamadı.', 404);
            return;
        }
        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId, $Rol, $Mevcut) {
            $Repo->yedekle($Id, 'bck_tbl_musteri', $KullaniciId);
            $Repo->softSil($Id, $KullaniciId, $Rol === 'superadmin' ? [] : ['EkleyenUserId' => $KullaniciId]);
            ActionLogger::delete('tbl_musteri', ['Id' => $Id, 'EkleyenUserId' => $Mevcut['EkleyenUserId'] ?? $KullaniciId]);
        });

        Response::json(['status' => 'ok']);
    }
}
