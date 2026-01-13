-- Create DB if not exists
IF DB_ID('NbtProject') IS NULL
BEGIN
    CREATE DATABASE NbtProject;
END
GO
USE NbtProject;
GO

:r 001_log_action.sql
:r 002_tnm_user.sql
:r 003_tbl_musteri.sql
:r 004_bck_tbl_musteri.sql
:r 005_seed_superadmin.sql
:r 006_bck_tnm_user.sql
:r 011_tbl_proje.sql
:r 012_bck_tbl_proje.sql
:r 013_tbl_teklif.sql
:r 014_bck_tbl_teklif.sql
:r 015_tbl_sozlesme.sql
:r 016_bck_tbl_sozlesme.sql
:r 017_tbl_teminat.sql
:r 018_bck_tbl_teminat.sql
:r 030_alter_tbl_musteri_add_fields.sql
