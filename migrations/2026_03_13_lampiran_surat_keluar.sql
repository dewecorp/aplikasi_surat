START TRANSACTION;

SET @db := DATABASE();

SELECT COUNT(*)
INTO @lampiran_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND TABLE_NAME = 'surat_keluar'
  AND COLUMN_NAME = 'lampiran';

SET @sql := IF(
  @lampiran_exists = 0,
  'ALTER TABLE surat_keluar ADD COLUMN lampiran VARCHAR(255) NULL AFTER file',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*)
INTO @file_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND TABLE_NAME = 'surat_keluar'
  AND COLUMN_NAME = 'file';

SELECT COUNT(*)
INTO @jenis_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND TABLE_NAME = 'surat_keluar'
  AND COLUMN_NAME = 'jenis_surat';

SET @sql := IF(
  @file_exists > 0 AND @jenis_exists > 0,
  "UPDATE surat_keluar
   SET lampiran = file
   WHERE (lampiran IS NULL OR lampiran = '')
     AND file IS NOT NULL AND file <> ''
     AND jenis_surat IN ('Undangan','Pemberitahuan')",
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;

