<?php
include 'config.php';

// Check data in surat_keputusan table
$query = mysqli_query($conn, "SELECT id, tgl_surat, no_surat, tentang, menimbang, mengingat, memperhatikan, lampiran FROM surat_keputusan LIMIT 5");

echo "<h2>Database Check - Surat Keputusan Data</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr>";
echo "<th>ID</th>";
echo "<th>No Surat</th>";
echo "<th>Tentang (Length)</th>";
echo "<th>Menimbang (Length)</th>";
echo "<th>Mengingat (Length)</th>";
echo "<th>Memperhatikan (Length)</th>";
echo "<th>Lampiran (Length)</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($query)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['no_surat'] . "</td>";
    echo "<td>" . strlen($row['tentang']) . " chars</td>";
    echo "<td>" . strlen($row['menimbang']) . " chars</td>";
    echo "<td>" . strlen($row['mengingat']) . " chars</td>";
    echo "<td>" . strlen($row['memperhatikan']) . " chars</td>";
    echo "<td>" . strlen($row['lampiran']) . " chars</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><hr><br>";

// Show detailed data for ID 1
echo "<h2>Detailed Data for ID 1</h2>";
$detail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM surat_keputusan WHERE id = 1"));
echo "<pre>";
print_r($detail);
echo "</pre>";
?>
