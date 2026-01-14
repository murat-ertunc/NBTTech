-- =============================================
-- NbtProject Veritabanı - Temiz Kurulum Script'i
-- =============================================
-- Bu dosya sqlcmd ile çalıştırılmalıdır:
-- sqlcmd -S localhost -U sa -P <password> -i 000_init_all.sql
-- =============================================

-- Veritabanı oluştur
IF DB_ID('NbtProject') IS NULL
BEGIN
    CREATE DATABASE NbtProject;
END
GO

USE NbtProject;
GO

-- =============================================
-- BÖLÜM 1: Log ve Kullanıcı Tabloları
-- =============================================
:r 001_log_action.sql
:r 002_tnm_user.sql
:r 006_bck_tnm_user.sql

-- =============================================
-- BÖLÜM 2: Müşteri Tablosu (Temel Tablo)
-- =============================================
:r 003_tbl_musteri.sql
:r 004_bck_tbl_musteri.sql

-- =============================================
-- BÖLÜM 3: Proje Tablosu (Müşteriye Bağlı)
-- =============================================
:r 011_tbl_proje.sql
:r 012_bck_tbl_proje.sql

-- =============================================
-- BÖLÜM 4: Teklif Tablosu (Müşteri ve Projeye Bağlı)
-- =============================================
:r 013_tbl_teklif.sql
:r 014_bck_tbl_teklif.sql

-- =============================================
-- BÖLÜM 5: Sözleşme Tablosu (Müşteri, Proje ve Teklife Bağlı)
-- =============================================
:r 015_tbl_sozlesme.sql
:r 016_bck_tbl_sozlesme.sql

-- =============================================
-- BÖLÜM 6: Teminat Tablosu (Müşteri ve Projeye Bağlı)
-- =============================================
:r 017_tbl_teminat.sql
:r 018_bck_tbl_teminat.sql

-- =============================================
-- BÖLÜM 7: Görüşme ve Kişi Tabloları
-- =============================================
:r 019_tbl_gorusme.sql
:r 020_bck_tbl_gorusme.sql
:r 021_tbl_kisi.sql
:r 022_bck_tbl_kisi.sql

-- =============================================
-- BÖLÜM 8: Damga Vergisi Tablosu
-- =============================================
:r 023_tbl_damgavergisi.sql
:r 024_bck_tbl_damgavergisi.sql

-- =============================================
-- BÖLÜM 9: Dosya Tablosu (Fatura ve Projeye Bağlı)
-- =============================================
:r 025_tbl_dosya.sql
:r 026_bck_tbl_dosya.sql

-- =============================================
-- BÖLÜM 10: Fatura Tablosu (Müşteri ve Projeye Bağlı)
-- =============================================
:r 007_tbl_fatura.sql
:r 008_bck_tbl_fatura.sql

-- =============================================
-- BÖLÜM 11: Ödeme Tablosu (Müşteri, Proje ve Faturaya Bağlı)
-- =============================================
:r 009_tbl_odeme.sql
:r 010_bck_tbl_odeme.sql

-- =============================================
-- BÖLÜM 12: Takvim Tablosu
-- =============================================
:r 027_tbl_takvim.sql

-- =============================================
-- BÖLÜM 13: Parametre Tablosu
-- =============================================
:r 028_tbl_parametre.sql

-- =============================================
-- BÖLÜM 14: Fatura Kalem Tablosu
-- =============================================
:r 030_tbl_fatura_kalem.sql

-- =============================================
-- BÖLÜM 15: Seed Data
-- =============================================
:r 005_seed_superadmin.sql
:r 029_seed_parametre.sql

PRINT 'NbtProject veritabanı başarıyla oluşturuldu!';
GO
