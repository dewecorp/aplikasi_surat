<?php
include 'config.php';

function to_romawi_local($number) {
    $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
    $returnValue = '';
    while ($number > 0) {
        foreach ($map as $roman => $int) {
            if($number >= $int) {
                $number -= $int;
                $returnValue .= $roman;
                break;
            }
        }
    }
    return $returnValue;
}

$q = mysqli_query($conn, "SELECT id, tgl_surat, no_surat FROM surat_keputusan");
echo "Starting sync...\n";
$count = 0;
while ($r = mysqli_fetch_assoc($q)) {
    $id = $r['id'];
    $tgl = $r['tgl_surat'];
    $old_no = $r['no_surat'];
    
    $tahun = date('Y', strtotime($tgl));
    $bulan = date('n', strtotime($tgl));
    $romawi = to_romawi_local($bulan);
    
    $no_parts = explode('/', $old_no);
    $prefix = $no_parts[0];
    
    $new_no = $prefix . '/MI.SF/SK/' . $romawi . '/' . $tahun;
    
    if ($new_no !== $old_no) {
        mysqli_query($conn, "UPDATE surat_keputusan SET no_surat = '$new_no' WHERE id = '$id'");
        echo "Updated ID $id: $old_no -> $new_no\n";
        $count++;
    }
}
echo "Finished. Updated $count records.\n";
?>