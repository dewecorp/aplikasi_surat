<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'sims';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Base URL
$base_url = "http://localhost/sims/";

function getRomawi($n){
    $hasil = "";
    $iromawi = array("","I","II","III","IV","V","VI","VII","VIII","IX","X","XI","XII");
    if(array_key_exists($n,$iromawi)){
        $hasil = $iromawi[$n];
    }
    return $hasil;
}

function tgl_indo($tanggal){
	$bulan = array (
		1 =>   'Januari',
		'Februari',
		'Maret',
		'April',
		'Mei',
		'Juni',
		'Juli',
		'Agustus',
		'September',
		'Oktober',
		'November',
		'Desember'
	);
	$pecahkan = explode('-', $tanggal);
	
	// variabel pecahkan 0 = tanggal
	// variabel pecahkan 1 = bulan
	// variabel pecahkan 2 = tahun
 
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

function hari_indo($tanggal){
    $hari = array ( 1 =>    'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
        'Minggu'
    );
    $num = date('N', strtotime($tanggal));
    return $hari[$num];
}

function log_activity($user_id, $type, $description) {
    global $conn;
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $type = mysqli_real_escape_string($conn, $type);
    $description = mysqli_real_escape_string($conn, $description);
    $query = "INSERT INTO activity_log (user_id, activity_type, description) VALUES ('$user_id', '$type', '$description')";
    mysqli_query($conn, $query);
}

function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes      = round($seconds / 60);
    $hours        = round($seconds / 3600);
    $days         = round($seconds / 86400);
    $weeks        = round($seconds / 604800);
    $months       = round($seconds / 2629440);
    $years        = round($seconds / 31553280);

    if($seconds <= 60) {
        return "Baru saja";
    } else if($minutes <= 60) {
        if($minutes==1){
            return "1 menit yang lalu";
        } else {
            return "$minutes menit yang lalu";
        }
    } else if($hours <= 24) {
        if($hours==1){
            return "1 jam yang lalu";
        } else {
            return "$hours jam yang lalu";
        }
    } else if($days <= 7) {
        if($days==1){
            return "kemarin";
        } else {
            return "$days hari yang lalu";
        }
    } else if($weeks <= 4.3) {
        if($weeks==1){
            return "1 minggu yang lalu";
        } else {
            return "$weeks minggu yang lalu";
        }
    } else if($months <= 12) {
        if($months==1){
            return "1 bulan yang lalu";
        } else {
            return "$months bulan yang lalu";
        }
    } else {
        if($years==1){
            return "1 tahun yang lalu";
        } else {
            return "$years tahun yang lalu";
        }
    }
}

// Auto delete logs older than 24 hours
mysqli_query($conn, "DELETE FROM activity_log WHERE timestamp < NOW() - INTERVAL 1 DAY");
