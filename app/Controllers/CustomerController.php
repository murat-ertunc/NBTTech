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
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        $Repo = new CustomerRepository();
        $Id = Transaction::wrap(function () use ($Repo, $Unvan, $Girdi, $KullaniciId) {
            return $Repo->ekle([
                'Unvan' => $Unvan,
                'Aciklama' => $Girdi['Aciklama'] ?? null,
                'MusteriKodu' => isset($Girdi['MusteriKodu']) ? trim((string) $Girdi['MusteriKodu']) : null,
                'VergiDairesi' => isset($Girdi['VergiDairesi']) ? trim((string) $Girdi['VergiDairesi']) : null,
                'VergiNo' => isset($Girdi['VergiNo']) ? trim((string) $Girdi['VergiNo']) : null,
                'Adres' => isset($Girdi['Adres']) ? trim((string) $Girdi['Adres']) : null,
                'Telefon' => isset($Girdi['Telefon']) ? trim((string) $Girdi['Telefon']) : null,
                'Faks' => isset($Girdi['Faks']) ? trim((string) $Girdi['Faks']) : null,
                'MersisNo' => isset($Girdi['MersisNo']) ? trim((string) $Girdi['MersisNo']) : null,
                'Web' => isset($Girdi['Web']) ? trim((string) $Girdi['Web']) : null,
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
            if (strlen($Girdi['Unvan']) < 2) {
                Response::error('Ünvan en az 2 karakter olmalıdır.', 422);
                return;
            }
        }
        $Repo = new CustomerRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
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
            $GuncellenecekVeri = [
                'Unvan' => $Girdi['Unvan'] ?? null,
                'Aciklama' => $Girdi['Aciklama'] ?? null,
            ];
            if (array_key_exists('MusteriKodu', $Girdi)) {
                $GuncellenecekVeri['MusteriKodu'] = trim((string) $Girdi['MusteriKodu']) ?: null;
            }
            if (array_key_exists('VergiDairesi', $Girdi)) {
                $GuncellenecekVeri['VergiDairesi'] = trim((string) $Girdi['VergiDairesi']) ?: null;
            }
            if (array_key_exists('VergiNo', $Girdi)) {
                $GuncellenecekVeri['VergiNo'] = trim((string) $Girdi['VergiNo']) ?: null;
            }
            if (array_key_exists('Adres', $Girdi)) {
                $GuncellenecekVeri['Adres'] = trim((string) $Girdi['Adres']) ?: null;
            }
            if (array_key_exists('Telefon', $Girdi)) {
                $GuncellenecekVeri['Telefon'] = trim((string) $Girdi['Telefon']) ?: null;
            }
            if (array_key_exists('Faks', $Girdi)) {
                $GuncellenecekVeri['Faks'] = trim((string) $Girdi['Faks']) ?: null;
            }
            if (array_key_exists('MersisNo', $Girdi)) {
                $GuncellenecekVeri['MersisNo'] = trim((string) $Girdi['MersisNo']) ?: null;
            }
            if (array_key_exists('Web', $Girdi)) {
                $GuncellenecekVeri['Web'] = trim((string) $Girdi['Web']) ?: null;
            }
            $Repo->guncelle($Id, $GuncellenecekVeri, $KullaniciId, in_array($Rol, ['superadmin', 'admin'], true) ? [] : ['EkleyenUserId' => $KullaniciId]);
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
