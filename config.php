<?php

$host = '127.0.0.1';
$dbname = 'absensi';
$user = 'absen';
$pass = 'absen123'; 
$port = '3308';// Nanti dihapus pas disisi produksi

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e){
    die('Koneksi Database gagal: ' . $e->getMessage());
}