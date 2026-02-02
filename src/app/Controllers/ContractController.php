<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Core\UploadValidator;
use App\Core\DownloadHelper;
use App\Repositories\ContractRepository;
use App\Services\CalendarService;

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
        // Hem JSON hem FormData gelen istekleri destekliyoruz
        $IcerikTipi = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($IcerikTipi, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {
            // multipart/form-data
            $Girdi = $_POST;
        }
        
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

        // Dosya yukleme islemi - PDF veya Word tek alanda saklanir
        $MaksimumBoyut = 10 * 1024 * 1024; // 10MB
        $DosyaAdi = null;
        $DosyaYolu = null;

        if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
            $Hata = UploadValidator::validateDocument(
                $_FILES['dosya'],
                $MaksimumBoyut
            );

            if ($Hata !== null) {
                Response::json(['errors' => ['dosya' => $Hata], 'message' => $Hata], 422);
                return;
            }

            $YuklemeKlasoru = STORAGE_PATH . 'uploads' . DIRECTORY_SEPARATOR;
            if (!is_dir($YuklemeKlasoru)) {
                mkdir($YuklemeKlasoru, 0755, true);
            }

            $OrijinalAd = $_FILES['dosya']['name'];
            $Uzanti = strtolower(pathinfo($OrijinalAd, PATHINFO_EXTENSION));
            $GuvenliAd = uniqid() . '_' . time() . '.' . $Uzanti;
            $HedefYol = $YuklemeKlasoru . $GuvenliAd;

            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $HedefYol)) {
                $DosyaAdi = $OrijinalAd;
                $DosyaYolu = 'storage/uploads/' . $GuvenliAd;
            }
        }

        $Repo = new ContractRepository();
        $YuklenecekVeri = [
            'MusteriId' => (int)$Girdi['MusteriId'],
            'ProjeId' => !empty($Girdi['ProjeId']) ? (int)$Girdi['ProjeId'] : null,
            'TeklifId' => !empty($Girdi['TeklifId']) ? (int)$Girdi['TeklifId'] : null,
            'SozlesmeTarihi' => $Girdi['SozlesmeTarihi'] ?? null,
            'Tutar' => isset($Girdi['Tutar']) ? (float)$Girdi['Tutar'] : 0.00,
            'ParaBirimi' => $Girdi['ParaBirimi'] ?? 'TRY',
            'DosyaAdi' => $DosyaAdi,
            'DosyaYolu' => $DosyaYolu,
            'Durum' => isset($Girdi['Durum']) ? (int)$Girdi['Durum'] : 1
        ];

        $Id = Transaction::wrap(function () use ($Repo, $YuklenecekVeri, $KullaniciId) {
            return $Repo->ekle($YuklenecekVeri, $KullaniciId);
        });

        // Takvim hatirlatmasi olustur - sozlesme tarihi varsa
        if (!empty($YuklenecekVeri['SozlesmeTarihi'])) {
            CalendarService::createOrUpdateReminder(
                (int)$YuklenecekVeri['MusteriId'],
                'sozlesme',
                $Id,
                $YuklenecekVeri['SozlesmeTarihi'],
                'Sözleşme Hatırlatma',
                $KullaniciId
            );
        }

        Response::json(['id' => $Id], 201);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        // Hem JSON hem FormData gelen istekleri destekliyoruz
        $IcerikTipi = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($IcerikTipi, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } elseif (strpos($IcerikTipi, 'multipart/form-data') !== false) {
            $Girdi = $_POST;
        } elseif (strpos($IcerikTipi, 'application/x-www-form-urlencoded') !== false) {
            parse_str(file_get_contents('php://input'), $Girdi);
        } else {
            $Girdi = $_POST;
        }
        
        $Repo = new ContractRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Guncellenecek = [];
        if (isset($Girdi['SozlesmeTarihi'])) $Guncellenecek['SozlesmeTarihi'] = $Girdi['SozlesmeTarihi'];
        if (isset($Girdi['Tutar'])) $Guncellenecek['Tutar'] = (float)$Girdi['Tutar'];
        if (isset($Girdi['ParaBirimi'])) $Guncellenecek['ParaBirimi'] = $Girdi['ParaBirimi'];
        if (isset($Girdi['Durum'])) $Guncellenecek['Durum'] = (int)$Girdi['Durum'];
        if (isset($Girdi['TeklifId'])) $Guncellenecek['TeklifId'] = (int)$Girdi['TeklifId'];
        if (array_key_exists('ProjeId', $Girdi)) $Guncellenecek['ProjeId'] = $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null;

        // PDF dosya silme veya guncelleme islemi
        if (!empty($Girdi['removeFile'])) {
            // Mevcut dosyayi sil
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut && !empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = SRC_PATH . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }
            $Guncellenecek['DosyaAdi'] = null;
            $Guncellenecek['DosyaYolu'] = null;
        }

        // Yeni dosya yuklendiyse eskisini silip yenisini kaydediyoruz
        if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
            $Hata = UploadValidator::validateDocument(
                $_FILES['dosya'],
                10 * 1024 * 1024
            );
            if ($Hata !== null) {
                Response::json(['errors' => ['dosya' => $Hata], 'message' => $Hata], 422);
                return;
            }

            // Eski dosyayi fiziksel olarak sil
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut && !empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = SRC_PATH . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }

            $YuklemeKlasoru = STORAGE_PATH . 'uploads' . DIRECTORY_SEPARATOR;
            if (!is_dir($YuklemeKlasoru)) {
                mkdir($YuklemeKlasoru, 0755, true);
            }

            $OrijinalAd = $_FILES['dosya']['name'];
            $Uzanti = strtolower(pathinfo($OrijinalAd, PATHINFO_EXTENSION));
            $GuvenliAd = uniqid() . '_' . time() . '.' . $Uzanti;
            $HedefYol = $YuklemeKlasoru . $GuvenliAd;

            if (move_uploaded_file($_FILES['dosya']['tmp_name'], $HedefYol)) {
                $Guncellenecek['DosyaAdi'] = $OrijinalAd;
                $Guncellenecek['DosyaYolu'] = 'storage/uploads/' . $GuvenliAd;
            }
        }


        if (!empty($Guncellenecek)) {
            Transaction::wrap(function () use ($Repo, $Id, $Guncellenecek, $KullaniciId) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            });
        }

        // Takvim hatirlatmasi guncelle - sozlesme tarihi varsa
        if (isset($Girdi['SozlesmeTarihi'])) {
            $Mevcut = $Repo->bul($Id);
            if ($Mevcut) {
                CalendarService::createOrUpdateReminder(
                    (int)$Mevcut['MusteriId'],
                    'sozlesme',
                    $Id,
                    $Girdi['SozlesmeTarihi'],
                    'Sözleşme Hatırlatma',
                    $KullaniciId
                );
            }
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

        $Repo = new ContractRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        // Takvim hatirlatmasini sil
        CalendarService::deleteReminder('sozlesme', $Id);

        Response::json(['status' => 'success']);
    }

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new ContractRepository();
        $Kayit = $Repo->bul($Id);
        
        if (!$Kayit) {
            Response::error('Kayit bulunamadi.', 404);
            return;
        }

        if (empty($Kayit['DosyaYolu'])) {
            Response::error('Bu kayita ait dosya bulunamadi.', 404);
            return;
        }

        $FilePath = SRC_PATH . $Kayit['DosyaYolu'];
        $DosyaAdi = $Kayit['DosyaAdi'] ?? basename($Kayit['DosyaYolu']);
        
        if (!file_exists($FilePath)) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }

        DownloadHelper::outputFile($FilePath, $DosyaAdi);
    }
}
