<?php

date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json'); // Tambahkan header JSON
header("Access-Control-Allow-Origin: *"); // Izinkan akses dari ESP32

$konek = mysqli_connect("localhost", "root", "", "dashboardsuhu");

if (!$konek) {
    die(json_encode([ // Response format JSON
        'status' => 'error',
        'message' => 'Koneksi gagal: ' . mysqli_connect_error()
    ]));
}

if (isset($_GET['suhu']) && isset($_GET['humi'])) {
    // Filter input
    $suhu = (float)$_GET['suhu'];
    $kelembaban = (float)$_GET['humi'];
    
    // Validasi range sensor
    if ($suhu < -40 || $suhu > 80 || $kelembaban < 0 || $kelembaban > 100) {
        die(json_encode([
            'status' => 'error',
            'message' => 'Nilai sensor tidak valid'
        ]));
    }

    $tanggal = date("Y-m-d H:i:s");
    
    // Gunakan prepared statement
    $stmt = $konek->prepare("INSERT INTO tb_sensor (tanggal, suhu, kelembaban) VALUES (?, ?, ?)");
    $stmt->bind_param("sdd", $tanggal, $suhu, $kelembaban);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'timestamp' => $tanggal
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menyimpan: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter tidak lengkap'
    ]);
}

mysqli_close($konek);
?>