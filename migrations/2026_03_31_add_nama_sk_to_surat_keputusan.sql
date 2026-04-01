-- Add nama_sk column to surat_keputusan table
ALTER TABLE surat_keputusan ADD COLUMN nama_sk VARCHAR(255) AFTER file;

-- Update existing records with default value (optional - can be updated manually)
-- UPDATE surat_keputusan SET nama_sk = CONCAT('SK_', id, '_', YEAR(tgl_surat)) WHERE nama_sk IS NULL;
