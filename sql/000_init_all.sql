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
:r 006_bck_tnm_user.sql
