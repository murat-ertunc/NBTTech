<?php
/**
 * Project Repository için veri erişim işlemlerini yürütür.
 * Sorgu ve kalıcılık katmanını soyutlar.
 */

namespace App\Repositories;

use App\Core\Transaction;
use App\Services\Logger\ActionLogger;

class ProjectRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_proje';

    public function tumAktifler(): array
    {
        $Sql = "SELECT p.*, m.Unvan AS MusteriUnvan
                FROM {$this->Tablo} p
                LEFT JOIN tbl_musteri m ON p.MusteriId = m.Id
                WHERE p.Sil = 0
                ORDER BY p.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriProjeleri(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC");
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriProjeleriPaginated(int $MusteriId, int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $Sayfa, $Limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }

    public function tumAktiflerPaginated(int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT p.*, m.Unvan AS MusteriUnvan
                FROM {$this->Tablo} p
                LEFT JOIN tbl_musteri m ON p.MusteriId = m.Id
                WHERE p.Sil = 0
                ORDER BY p.Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, [], $Sayfa, $Limit);
        $this->logSelect(['Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }

    public function cascadeSoftSil(int $Id, int $KullaniciId): void
    {
        Transaction::wrap(function() use ($Id, $KullaniciId) {

            // Önce projeyi yedekle, sonra soft-delete
            $this->yedekle($Id, 'bck_tbl_proje', $KullaniciId);
            $this->softSil($Id, $KullaniciId);

            $IliskiliTablolar = [
                'tbl_fatura'       => 'bck_tbl_fatura',
                'tbl_odeme'        => 'bck_tbl_odeme',
                'tbl_teklif'       => 'bck_tbl_teklif',
                'tbl_sozlesme'     => 'bck_tbl_sozlesme',
                'tbl_teminat'      => 'bck_tbl_teminat',
                'tbl_gorusme'      => 'bck_tbl_gorusme',
                'tbl_kisi'         => 'bck_tbl_kisi',
                'tbl_damgavergisi' => 'bck_tbl_damgavergisi',
                'tbl_dosya'        => 'bck_tbl_dosya'
            ];

            foreach ($IliskiliTablolar as $Tablo => $YedekTablo) {
                // İlişkili kayıtları yedekle
                $IdlerStmt = $this->Db->prepare("SELECT Id FROM {$Tablo} WHERE ProjeId = :ProjeId AND Sil = 0");
                $IdlerStmt->execute(['ProjeId' => $Id]);
                $Idler = $IdlerStmt->fetchAll(\PDO::FETCH_COLUMN);

                if (!empty($Idler)) {
                    // Her ilişkili kaydı yedekle
                    foreach ($Idler as $IliskiliId) {
                        try {
                            $Kayit = $this->Db->prepare("SELECT * FROM {$Tablo} WHERE Id = :Id");
                            $Kayit->execute(['Id' => $IliskiliId]);
                            $KayitVeri = $Kayit->fetch(\PDO::FETCH_ASSOC);
                            if ($KayitVeri) {
                                $KaynakId = $KayitVeri['Id'];
                                unset($KayitVeri['Id']);
                                $KayitVeri['KaynakId'] = $KaynakId;
                                $KayitVeri['BackupZamani'] = date('Y-m-d H:i:s');
                                $KayitVeri['BackupUserId'] = $KullaniciId;

                                $Kolonlar = implode(', ', array_keys($KayitVeri));
                                $Placeholders = implode(', ', array_map(fn($k) => ':' . $k, array_keys($KayitVeri)));
                                $YedekSql = "INSERT INTO {$YedekTablo} ({$Kolonlar}) VALUES ({$Placeholders})";
                                $YedekStmt = $this->Db->prepare($YedekSql);
                                $YedekStmt->execute($KayitVeri);
                            }
                        } catch (\Throwable $E) {
                            // Yedek tablosu yoksa engelleme — sadece logla
                            error_log("[ProjectRepository] Cascade yedekleme uyarısı ({$YedekTablo}): " . $E->getMessage());
                        }
                    }
                }

                // İlişkili kayıtları soft-delete et
                $Sql = "UPDATE {$Tablo} SET
                        Sil = 1,
                        DegisiklikZamani = SYSUTCDATETIME(),
                        DegistirenUserId = :UserId
                        WHERE ProjeId = :ProjeId AND Sil = 0";
                $Stmt = $this->Db->prepare($Sql);
                $Stmt->execute(['ProjeId' => $Id, 'UserId' => $KullaniciId]);

                $SilinenSayisi = $Stmt->rowCount();
                if ($SilinenSayisi > 0) {
                    ActionLogger::logla('CASCADE_DELETE', $Tablo, null, [
                        'ProjeId' => $Id,
                        'SilinenKayitSayisi' => $SilinenSayisi
                    ]);
                }
            }
        });
    }
}
