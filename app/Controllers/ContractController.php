<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\ContractRepository;

class ContractController
{
    public static function index(): void
    {
        $Repo = new ContractRepository();
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
                $Sonuc = $Repo->musteriSozlesmeleriPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriSozlesmeleri($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            // Standalone sayfa - pagination ile tum sozlesmeler
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $Sonuc = $Repo->tumAktiflerPaginated($Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->tumAktifler();
                Response::json(['data' => $Satirlar]);
            }
        }
    }

    /**
     * Tek Sozlesme Detayi Getir
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

        $Repo = new ContractRepository();
        $Sozlesme = $Repo->bul($Id);

        if (!$Sozlesme) {
            Response::error('Sozlesme bulunamadi.', 404);
            return;
        }

        Response::json(['data' => $Sozlesme]);
    }

    public static function store(): void
    {
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Zorunlu = ['MusteriId'];
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

        $Repo = new ContractRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => !empty($Girdi['ProjeId']) ? (int)$Girdi['ProjeId'] : null,
            'TeklifId' => !empty($Girdi['TeklifId']) ? (int)$Girdi['TeklifId'] : null,
            'SozlesmeTarihi' => $Girdi['SozlesmeTarihi'] ?? null,
            'Tutar' => isset($Girdi['Tutar']) ? (float)$Girdi['Tutar'] : 0.00,
            'ParaBirimi' => $Girdi['ParaBirimi'] ?? 'TRY',
            'DosyaYolu' => $Girdi['DosyaYolu'] ?? null,
            'Durum' => isset($Girdi['Durum']) ? (int)$Girdi['Durum'] : 1
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
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new ContractRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['SozlesmeTarihi'])) $Guncellenecek['SozlesmeTarihi'] = $Girdi['SozlesmeTarihi'];
            if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
            if (isset($Girdi['ParaBirimi'])) $Guncellenecek['ParaBirimi'] = $Girdi['ParaBirimi'];
            if (isset($Girdi['DosyaYolu'])) $Guncellenecek['DosyaYolu'] = $Girdi['DosyaYolu'];
            if (isset($Girdi['Durum'])) $Guncellenecek['Durum'] = (int)$Girdi['Durum'];
            if (isset($Girdi['TeklifId'])) $Guncellenecek['TeklifId'] = (int)$Girdi['TeklifId'];
            if (array_key_exists('ProjeId', $Girdi)) $Guncellenecek['ProjeId'] = $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null;

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
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new ContractRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }
}
