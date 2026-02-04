<?php
require_once 'session_init.php';
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access");
}

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set Header
$headers = ['NUPTK', 'NAMA GURU', 'JENIS KELAMIN (L/P)', 'TEMPAT LAHIR', 'TANGGAL LAHIR (YYYY-MM-DD)', 'STATUS (Guru Kelas/Guru Mapel)'];
$sheet->fromArray([$headers], NULL, 'A1');

// Set Column Width
foreach (range('A', 'F') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Add example data
$exampleData = [
    ['1234567890123456', 'Contoh Guru', 'L', 'Jakarta', '1990-01-01', 'Guru Kelas'],
];
$sheet->fromArray($exampleData, NULL, 'A2');

// Style Header
$headerStyle = [
    'font' => ['bold' => true],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFCCCCCC'],
    ],
];
$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_guru.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
