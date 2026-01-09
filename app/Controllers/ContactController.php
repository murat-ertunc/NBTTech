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
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }
        
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        if ($MusteriId > 0) {
            $Satirlar = $Repo->musteriKisileri($MusteriId);
        } else {
            $Satirlar = $Repo->tumAktifler();
        }
        Response::json(['data' => $Satirlar]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId', 'AdSoyad'];
        foreach ($Zorunlu as $Alan) {
            if (empty($Girdi[$Alan])) {
                Response::error("$Alan alanı zorunludur.", 422);
                return;
            }
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        $Repo = new ContactRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'AdSoyad' => trim((string)$Girdi['AdSoyad']),
            'Unvan' => isset($Girdi['Unvan']) ? trim((string)$Girdi['Unvan']) : null,
            'Telefon' => isset($Girdi['Telefon']) ? trim((string)$Girdi['Telefon']) : null,
            'Email' => isset($Girdi['Email']) ? trim((string)$Girdi['Email']) : null,
            'Notlar' => isset($Girdi['Notlar']) ? trim((string)$Girdi['Notlar']) : null
        ];

        $Id = Transaction::wrap(function () use ($Repo, $YuklenecekVeri, $KullaniciId) {
            return $Repo->ekle($YuklenecekVeri, $KullaniciId);
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
        $Repo = new ContactRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['AdSoyad'])) $Guncellenecek['AdSoyad'] = trim((string)$Girdi['AdSoyad']);
            if (isset($Girdi['Unvan'])) $Guncellenecek['Unvan'] = trim((string)$Girdi['Unvan']);
            if (isset($Girdi['Telefon'])) $Guncellenecek['Telefon'] = trim((string)$Girdi['Telefon']);
            if (isset($Girdi['Email'])) $Guncellenecek['Email'] = trim((string)$Girdi['Email']);
            if (isset($Girdi['Notlar'])) $Guncellenecek['Notlar'] = trim((string)$Girdi['Notlar']);

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
        });

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Repo = new ContactRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }
}
