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
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }
        $Rol = Context::rol();
        if (in_array($Rol, ['superadmin', 'admin'], true)) {
            // Superadmin ve admin tum musterileri ekleyen kullanici bilgisiyle gorsun
            $Satirlar = $Repo->tumAktiflerSiraliKullaniciBilgisiIle();
        } else {
            $Satirlar = $Repo->kullaniciyaGoreAktifler($KullaniciId);
        }
        Response::json(['data' => $Satirlar]);
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
        if (strlen($Unvan) < 2) {
            Response::error('Ünvan en az 2 karakter olmalıdır.', 422);
            return;
        }
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }
        $Repo = new CustomerRepository();
        $Id = Transaction::wrap(function () use ($Repo, $Unvan, $Girdi, $KullaniciId) {
            $YeniId = $Repo->ekle([
                'Unvan' => $Unvan,
                'Aciklama' => $Girdi['Aciklama'] ?? null,
            ], $KullaniciId);
            ActionLogger::insert('tbl_musteri', ['Id' => $YeniId, 'Unvan' => $Unvan, 'EkleyenUserId' => $KullaniciId]);
            return $YeniId;
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
            if (strlen($Girdi['Unvan']) < 2) {
                Response::error('Ünvan en az 2 karakter olmalıdır.', 422);
                return;
            }
        }
        $Repo = new CustomerRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }
        $Rol = Context::rol();
        $Mevcut = in_array($Rol, ['superadmin', 'admin'], true)
            ? $Repo->bul($Id)
            : $Repo->sahipliKayitBul($Id, $KullaniciId);
        if (!$Mevcut) {
            Response::error('Müşteri bulunamadı.', 404);
            return;
        }
        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId, $Rol, $Mevcut) {
            $Repo->yedekle($Id, 'bck_tbl_musteri', $KullaniciId);
            $Repo->guncelle($Id, [
                'Unvan' => $Girdi['Unvan'] ?? null,
                'Aciklama' => $Girdi['Aciklama'] ?? null,
            ], $KullaniciId, in_array($Rol, ['superadmin', 'admin'], true) ? [] : ['EkleyenUserId' => $KullaniciId]);
            ActionLogger::update('tbl_musteri', ['Id' => $Id, 'EkleyenUserId' => $Mevcut['EkleyenUserId'] ?? $KullaniciId], $Girdi);
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
            Response::error('Kullanıcı bilgisi bulunamadı.', 401);
            return;
        }
        $Rol = Context::rol();
        $Mevcut = in_array($Rol, ['superadmin', 'admin'], true)
            ? $Repo->bul($Id)
            : $Repo->sahipliKayitBul($Id, $KullaniciId);
        if (!$Mevcut) {
            Response::error('Müşteri bulunamadı.', 404);
            return;
        }
        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId, $Rol, $Mevcut) {
            $Repo->yedekle($Id, 'bck_tbl_musteri', $KullaniciId);
            $Repo->softSil($Id, $KullaniciId, in_array($Rol, ['superadmin', 'admin'], true) ? [] : ['EkleyenUserId' => $KullaniciId]);
            ActionLogger::delete('tbl_musteri', ['Id' => $Id, 'EkleyenUserId' => $Mevcut['EkleyenUserId'] ?? $KullaniciId]);
        });

        Response::json(['status' => 'ok']);
    }
}
