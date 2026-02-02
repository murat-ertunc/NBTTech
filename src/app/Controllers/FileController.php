<?php
/**
 * File Controller için HTTP isteklerini yönetir.
 * Gelen talepleri doğrular ve yanıt akışını oluşturur.
 */

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\FileRepository;

class FileController
{
    private static function getUploadDir(): string
    {
        return STORAGE_PATH . 'uploads' . DIRECTORY_SEPARATOR;
    }

    public static function index(): void
    {
        $Repo = new FileRepository();
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
                $Sonuc = $Repo->musteriDosyalariPaginated($MusteriId, $Sayfa, $Limit);
                Response::json($Sonuc);
            } else {
                $Satirlar = $Repo->musteriDosyalari($MusteriId);
                Response::json(['data' => $Satirlar]);
            }
        } else {
            $Satirlar = $Repo->tumAktifler();
            Response::json(['data' => $Satirlar]);
        }
    }

    public static function store(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $DosyaAnahtari = isset($_FILES['file']) ? 'file' : (isset($_FILES['dosya']) ? 'dosya' : null);
        if (!$DosyaAnahtari || $_FILES[$DosyaAnahtari]['error'] !== UPLOAD_ERR_OK) {
            $HataMesajlari = [
                UPLOAD_ERR_INI_SIZE => 'Dosya boyutu sunucu limitini asiyor.',
                UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu form limitini asiyor.',
                UPLOAD_ERR_PARTIAL => 'Dosya kismen yuklendi.',
                UPLOAD_ERR_NO_FILE => 'Dosya secilmedi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Gecici klasor bulunamadi.',
                UPLOAD_ERR_CANT_WRITE => 'Dosya yazilamadi.',
                UPLOAD_ERR_EXTENSION => 'Dosya uzantisi engellendi.'
            ];
            $HataKodu = $_FILES[$DosyaAnahtari]['error'] ?? UPLOAD_ERR_NO_FILE;
            $HataMesaji = $HataMesajlari[$HataKodu] ?? 'Dosya yuklenemedi.';
            Response::error($HataMesaji, 422);
            return;
        }

        $FaturaId = isset($_POST['FaturaId']) ? (int)$_POST['FaturaId'] : null;
        $MusteriId = isset($_POST['MusteriId']) ? (int)$_POST['MusteriId'] : 0;

        if ($FaturaId && $MusteriId <= 0) {
            $FaturaRepo = new \App\Repositories\InvoiceRepository();
            $Fatura = $FaturaRepo->bul($FaturaId);
            if ($Fatura) {
                $MusteriId = (int)$Fatura['MusteriId'];
            }
        }

        if ($MusteriId <= 0) {
            Response::error('MusteriId alani zorunludur.', 422);
            return;
        }

        $DosyaBilgisi = $_FILES[$DosyaAnahtari];
        $OriginalName = $DosyaBilgisi['name'];
        $DosyaBoyutu = $DosyaBilgisi['size'];
        $DosyaTipi = $DosyaBilgisi['type'];
        $GeciciYol = $DosyaBilgisi['tmp_name'];

        $MaksimumBoyut = 10 * 1024 * 1024;
        if ($DosyaBoyutu > $MaksimumBoyut) {
            $BoyutMB = round($DosyaBoyutu / (1024 * 1024), 2);
            $MaksimumMB = round($MaksimumBoyut / (1024 * 1024));
            Response::error("Dosya boyutu cok buyuk ({$BoyutMB}MB). Maksimum {$MaksimumMB}MB yuklenebilir.", 422);
            return;
        }

        $Uzanti = strtolower(pathinfo($OriginalName, PATHINFO_EXTENSION));

        $GuvenliAd = uniqid() . '_' . time() . '.' . $Uzanti;
        $UploadDir = self::getUploadDir();

        if (!is_dir($UploadDir)) {
            mkdir($UploadDir, 0755, true);
        }

        $HedefYol = $UploadDir . $GuvenliAd;

        if (!move_uploaded_file($GeciciYol, $HedefYol)) {
            Response::error('Dosya kaydedilemedi.', 500);
            return;
        }

        $Repo = new FileRepository();
        $YuklenecekVeri = [
            'MusteriId' => $MusteriId,
            'ProjeId' => isset($_POST['ProjeId']) && $_POST['ProjeId'] ? (int)$_POST['ProjeId'] : null,
            'FaturaId' => $FaturaId,
            'DosyaAdi' => $OriginalName,
            'DosyaYolu' => 'storage/uploads/' . $GuvenliAd,
            'DosyaTipi' => $DosyaTipi,
            'DosyaBoyutu' => $DosyaBoyutu,
            'Aciklama' => isset($_POST['Aciklama']) ? trim((string)$_POST['Aciklama']) : null
        ];

        try {
            $Id = $Repo->ekle($YuklenecekVeri, $KullaniciId);
            Response::json(['id' => $Id, 'path' => $YuklenecekVeri['DosyaYolu']], 201);
        } catch (\Exception $E) {
            if (file_exists($HedefYol)) {
                unlink($HedefYol);
            }
            Response::error('Dosya kaydedilemedi: ' . $E->getMessage(), 500);
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
        $Repo = new FileRepository();
        $Dosya = $Repo->bul($Id);
        if (!$Dosya) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }
        Response::json(['data' => $Dosya]);
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $IcerikTipi = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($IcerikTipi, 'application/json') !== false) {
            $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {
            $Girdi = $_POST;
        }
        $Repo = new FileRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }

        $DosyaAnahtari = isset($_FILES['file']) ? 'file' : (isset($_FILES['dosya']) ? 'dosya' : null);
        $YeniDosya = null;
        if ($DosyaAnahtari && $_FILES[$DosyaAnahtari]['error'] === UPLOAD_ERR_OK) {
            $DosyaBilgisi = $_FILES[$DosyaAnahtari];
            $OriginalName = $DosyaBilgisi['name'];
            $DosyaBoyutu = $DosyaBilgisi['size'];
            $DosyaTipi = $DosyaBilgisi['type'];
            $GeciciYol = $DosyaBilgisi['tmp_name'];

            $MaksimumBoyut = 10 * 1024 * 1024;
            if ($DosyaBoyutu > $MaksimumBoyut) {
                $BoyutMB = round($DosyaBoyutu / (1024 * 1024), 2);
                Response::error("Dosya boyutu cok buyuk ({$BoyutMB}MB). Maksimum 10MB yuklenebilir.", 422);
                return;
            }

            $Uzanti = strtolower(pathinfo($OriginalName, PATHINFO_EXTENSION));

            $GuvenliAd = uniqid() . '_' . time() . '.' . $Uzanti;
            $UploadDir = self::getUploadDir();
            if (!is_dir($UploadDir)) {
                mkdir($UploadDir, 0755, true);
            }

            $HedefYol = $UploadDir . $GuvenliAd;
            if (!move_uploaded_file($GeciciYol, $HedefYol)) {
                Response::error('Dosya kaydedilemedi.', 500);
                return;
            }

            $YeniDosya = [
                'DosyaAdi' => $OriginalName,
                'DosyaYolu' => 'storage/uploads/' . $GuvenliAd,
                'DosyaTipi' => $DosyaTipi,
                'DosyaBoyutu' => $DosyaBoyutu
            ];
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['Aciklama'])) $Guncellenecek['Aciklama'] = trim((string)$Girdi['Aciklama']);
            if (array_key_exists('ProjeId', $Girdi)) $Guncellenecek['ProjeId'] = $Girdi['ProjeId'] ? (int)$Girdi['ProjeId'] : null;

            if (!empty($Guncellenecek)) {
                $Repo->guncelle($Id, $Guncellenecek, $KullaniciId);
            }
        });

        if ($YeniDosya) {

            if (!empty($Mevcut['DosyaYolu'])) {
                $EskiDosyaYolu = SRC_PATH . $Mevcut['DosyaYolu'];
                if (file_exists($EskiDosyaYolu)) {
                    unlink($EskiDosyaYolu);
                }
            }
            $Repo->guncelle($Id, $YeniDosya, $KullaniciId);
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

        $Repo = new FileRepository();
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

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Gecersiz kayit.', 422);
            return;
        }

        $Repo = new FileRepository();
        $Dosya = $Repo->bul($Id);

        if (!$Dosya) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }

        $TamDosyaYolu = SRC_PATH . $Dosya['DosyaYolu'];

        if (!file_exists($TamDosyaYolu)) {
            Response::error('Dosya bulunamadi.', 404);
            return;
        }

        header('Content-Type: ' . ($Dosya['DosyaTipi'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $Dosya['DosyaAdi'] . '"');
        header('Content-Length: ' . filesize($TamDosyaYolu));
        readfile($TamDosyaYolu);
        exit;
    }
}
