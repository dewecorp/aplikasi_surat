-- Add columns for flexible lampiran method
ALTER TABLE surat_keputusan ADD COLUMN file_lampiran VARCHAR(255) AFTER nama_sk;
ALTER TABLE surat_keputusan ADD COLUMN lampiran_method ENUM('ckeditor', 'file') DEFAULT 'ckeditor' AFTER file_lampiran;
