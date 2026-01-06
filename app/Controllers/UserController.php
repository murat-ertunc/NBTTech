<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\CustomerRepository;
use App\Repositories\UserRepository;
use App\Services\Logger\ActionLogger;

class UserController
{
    public static function index(): void
    {
        $Repo = new UserRepository();
        $Satirlar = $Repo->tumKullanicilar();
        Response::json(['data' => $Satirlar]);
    }

    public static function block(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kullanıcı.', 422);
            return;
        }
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!array_key_exists('Aktif', $Girdi)) {
            Response::error('Aktif alanı zorunludur.', 422);
            return;
        }
        $Aktif = (int) $Girdi['Aktif'] === 1 ? 1 : 0;
        $Repo = new UserRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Kullanıcı bulunamadı.', 404);
            return;
        }
        if (($Mevcut['Rol'] ?? '') === 'superadmin') {
            Response::error('Süper admin bloklanamaz.', 403);
            return;
        }
        Transaction::wrap(function () use ($Repo, $Id, $Aktif) {
            $Repo->yedekle($Id, 'bck_tnm_user', Context::kullaniciId());
            $Repo->guncelle($Id, ['Aktif' => $Aktif], Context::kullaniciId());
            $Islem = $Aktif === 1 ? 'unblock' : 'block';
            ActionLogger::logla($Islem, 'tnm_user', ['Id' => $Id, 'Aktif' => $Aktif], 'ok');
        });

        Response::json(['status' => 'ok']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kullanıcı.', 422);
            return;
        }
        $Repo = new UserRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Kullanıcı bulunamadı.', 404);
            return;
        }
        if (($Mevcut['Rol'] ?? '') === 'superadmin') {
            Response::error('Süper admin silinemez.', 403);
            return;
        }
        
        $SilenKullaniciId = Context::kullaniciId();
        
        Transaction::wrap(function () use ($Id, $SilenKullaniciId, $Repo) {
            $MusteriRepo = new CustomerRepository();
            $SilinenMusteriSayisi = $MusteriRepo->kullanicininMusterileriniSil($Id, $SilenKullaniciId);
            if ($SilinenMusteriSayisi > 0) {
                ActionLogger::delete('tbl_musteri', ['EkleyenUserId' => $Id, 'SilinenAdet' => $SilinenMusteriSayisi]);
            }

            // Delete oncesi yedekleme (ProjeYazimKurallari kurali)
            $Repo->yedekle($Id, 'bck_tnm_user', $SilenKullaniciId);
            $Repo->softSil($Id, $SilenKullaniciId);
            ActionLogger::delete('tnm_user', ['Id' => $Id, 'BagliSilinenMusteri' => $SilinenMusteriSayisi]);
        });

        Response::json(['status' => 'ok']);
    }
}
