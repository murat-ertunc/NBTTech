<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\FileRepository;

class FileController
{
    private static string $uploadDir = __DIR__ . '/../../storage/uploads/';

    public static function index(): void
    {
        $Repo = new FileRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }
        
        $MusteriId = isset($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : 0;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);

        if ($MusteriId > 0) {
            if (isset($_GET['page']) || isset($_GET['limit'])) {
                $result = $Repo->musteriDosyalariPaginated($MusteriId, $page, $limit);
                Response::json($result);
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
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Dosya boyutu sunucu limitini aşıyor.',
                UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu form limitini aşıyor.',
                UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi.',
                UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör bulunamadı.',
                UPLOAD_ERR_CANT_WRITE => 'Dosya yazılamadı.',
                UPLOAD_ERR_EXTENSION => 'Dosya uzantısı engellendi.'
            ];
            $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
            $errorMsg = $errorMessages[$errorCode] ?? 'Dosya yüklenemedi.';
            Response::error($errorMsg, 422);
            return;
        }

        $MusteriId = isset($_POST['MusteriId']) ? (int)$_POST['MusteriId'] : 0;
        if ($MusteriId <= 0) {
            Response::error('MusteriId alanı zorunludur.', 422);
            return;
        }

        $File = $_FILES['file'];
        $OriginalName = $File['name'];
        $FileSize = $File['size'];
        $FileType = $File['type'];
        $TempPath = $File['tmp_name'];

        $maxSize = 10 * 1024 * 1024;
        if ($FileSize > $maxSize) {
            $sizeMB = round($FileSize / (1024 * 1024), 2);
            Response::error("Dosya boyutu çok büyük ({$sizeMB}MB). Maksimum 10MB yüklenebilir.", 422);
            return;
        }

        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
            'application/zip',
            'application/x-rar-compressed',
            'application/octet-stream'
        ];
        
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip', 'rar'];
        $Extension = strtolower(pathinfo($OriginalName, PATHINFO_EXTENSION));
        
        if (!in_array($Extension, $allowedExtensions)) {
            Response::error('Bu dosya türü desteklenmiyor. İzin verilen türler: PDF, Word, Excel, Resimler, TXT, ZIP, RAR', 422);
            return;
        }

        $SafeName = uniqid() . '_' . time() . '.' . $Extension;
        
        if (!is_dir(self::$uploadDir)) {
            mkdir(self::$uploadDir, 0755, true);
        }

        $DestPath = self::$uploadDir . $SafeName;
        
        if (!move_uploaded_file($TempPath, $DestPath)) {
            Response::error('Dosya kaydedilemedi.', 500);
            return;
        }

        $Repo = new FileRepository();
        $YuklenecekVeri = [
            'MusteriId' => $MusteriId,
            'ProjeId' => isset($_POST['ProjeId']) && $_POST['ProjeId'] ? (int)$_POST['ProjeId'] : null,
            'DosyaAdi' => $OriginalName,
            'DosyaYolu' => 'storage/uploads/' . $SafeName,
            'DosyaTipi' => $FileType,
            'DosyaBoyutu' => $FileSize,
            'Aciklama' => isset($_POST['Aciklama']) ? trim((string)$_POST['Aciklama']) : null
        ];

        try {
            $Id = $Repo->ekle($YuklenecekVeri, $KullaniciId);
            Response::json(['id' => $Id, 'path' => $YuklenecekVeri['DosyaYolu']], 201);
        } catch (\Exception $e) {
            if (file_exists($DestPath)) {
                unlink($DestPath);
            }
            Response::error('Dosya kaydedilemedi: ' . $e->getMessage(), 500);
        }
    }

    public static function update(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new FileRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $KullaniciId) {
            $Guncellenecek = [];
            if (isset($Girdi['Aciklama'])) $Guncellenecek['Aciklama'] = trim((string)$Girdi['Aciklama']);

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

        $Repo = new FileRepository();
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum geçersiz veya süresi dolmuş.', 401);
            return;
        }

        // Dosyayı fiziksel olarak silmiyoruz, sadece soft delete
        Transaction::wrap(function () use ($Repo, $Id, $KullaniciId) {
            $Repo->softSil($Id, $KullaniciId);
        });

        Response::json(['status' => 'success']);
    }

    public static function download(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        if ($Id <= 0) {
            Response::error('Geçersiz kayıt.', 422);
            return;
        }

        $Repo = new FileRepository();
        $Dosya = $Repo->bul($Id);
        
        if (!$Dosya) {
            Response::error('Dosya bulunamadı.', 404);
            return;
        }

        $FilePath = __DIR__ . '/../../' . $Dosya['DosyaYolu'];
        
        if (!file_exists($FilePath)) {
            Response::error('Dosya bulunamadı.', 404);
            return;
        }

        header('Content-Type: ' . ($Dosya['DosyaTipi'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $Dosya['DosyaAdi'] . '"');
        header('Content-Length: ' . filesize($FilePath));
        readfile($FilePath);
        exit;
    }
}
