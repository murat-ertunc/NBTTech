<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\DistrictRepository;
use App\Repositories\CityRepository;




class DistrictController
{
    public static function index(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new DistrictRepository();
        
        
        $SehirId = isset($_GET['sehir_id']) ? (int)$_GET['sehir_id'] : 0;
        
        if ($SehirId > 0) {
            $Satirlar = $Repo->sehireGore($SehirId);
        } else {
            $Satirlar = $Repo->tumAktifler();
        }
        
        Response::json(['data' => $Satirlar]);
    }

    public static function show(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int)$Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new DistrictRepository();
        $Ilce = $Repo->bul($Id);
        if (!$Ilce) {
            Response::error('Ilce bulunamadi.', 404);
            return;
        }

        Response::json($Ilce);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        if (empty($Girdi['SehirId'])) {
            Response::error('SehirId alani zorunludur.', 422);
            return;
        }
        if (empty($Girdi['Ad'])) {
            Response::error('Ad alani zorunludur.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        
        $CityRepo = new CityRepository();
        $Sehir = $CityRepo->bul((int)$Girdi['SehirId']);
        if (!$Sehir) {
            Response::error('Gecersiz sehir.', 422);
            return;
        }

        $Repo = new DistrictRepository();
        
        
        $Mevcut = $Repo->sehirVeAdIleBul((int)$Girdi['SehirId'], trim($Girdi['Ad']));
        if ($Mevcut) {
            Response::error('Bu sehirde bu isimde bir ilce zaten mevcut.', 422);
            return;
        }

        $Id = Transaction::wrap(function () use ($Repo, $Girdi, $KullaniciId) {
            return $Repo->ekle([
                'SehirId' => (int)$Girdi['SehirId'],
                'Ad' => trim($Girdi['Ad'])
            ], $KullaniciId);
        });

        Response::json(['id' => $Id], 201);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int)$Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new DistrictRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Ilce bulunamadi.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['SehirId'])) $Guncellenecek['SehirId'] = (int)$Girdi['SehirId'];
            if (isset($Girdi['Ad'])) $Guncellenecek['Ad'] = trim($Girdi['Ad']);

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
        });

        Response::json(['status' => 'success']);
    }

    public static function delete(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int)$Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Repo = new DistrictRepository();
        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Ilce bulunamadi.', 404);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }
}
