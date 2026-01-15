<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\ContactRepository;

class ContactController
{
    public static function index(): void
    {
        $Repo = new ContactRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);

        if ($MusteriId > 0) {
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->musteriKisileriPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriKisileri($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            $Satirlar = $Repo->tumAktifler();
            Response::json(['data' => $Satirlar]);
        }
    }

    /**
     * Tek Kisi Detayi Getir
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

        $Repo = new ContactRepository();
        $Kisi = $Repo->bul($Id);

        if (!$Kisi) {
            Response::error('Kisi bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Kisi]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'ProjeId', 'AdSoyad'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alani zorunludur.", 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new ContactRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => (int)$Girdi['ProjeId'],
            'AdSoyad' => trim((string)$Girdi['AdSoyad']),
            'Unvan' => isset($Girdi['Unvan']) ? trim((string)$Girdi['Unvan']) : null,
            'Telefon' => isset($Girdi['Telefon']) ? trim((string)$Girdi['Telefon']) : null,
            'Email' => isset($Girdi['Email']) ? trim((string)$Girdi['Email']) : null,
            'Notlar' => isset($Girdi['Notlar']) ? trim((string)$Girdi['Notlar']) : null
        ];

        $Id = $Repo->ekle($YuklenecekVeri, $KullaniciId);

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
        $Repo = new ContactRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Guncellenecek = [];
        if (isset($Girdi['ProjeId'])) $Guncellenecek['ProjeId'] = (int)$Girdi['ProjeId'];
        if (isset($Girdi['AdSoyad'])) $Guncellenecek['AdSoyad'] = trim((string)$Girdi['AdSoyad']);
        if (isset($Girdi['Unvan'])) $Guncellenecek['Unvan'] = trim((string)$Girdi['Unvan']);
        if (isset($Girdi['Telefon'])) $Guncellenecek['Telefon'] = trim((string)$Girdi['Telefon']);
        if (isset($Girdi['Email'])) $Guncellenecek['Email'] = trim((string)$Girdi['Email']);
        if (isset($Girdi['Notlar'])) $Guncellenecek['Notlar'] = trim((string)$Girdi['Notlar']);

        if (!empty($Guncellenecek)) {
            $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
        }

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new ContactRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo->softSil($Id, $KullaniciId);

        Response::json(['status' => 'success']);
    }
}
