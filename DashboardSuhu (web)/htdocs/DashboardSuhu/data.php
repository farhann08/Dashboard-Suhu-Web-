<?php

date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

// Database connection
$konek = mysqli_connect("localhost", "root", "", "dashboardsuhu");
if (!$konek) {
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'error' => 'Database connection failed',
        'message' => mysqli_connect_error()
    ]));
}

// Optimized query
$query = mysqli_query($konek, 
    "SELECT 
        DATE_FORMAT(tanggal, '%H:%i:%s') as waktu, 
        suhu, 
        kelembaban 
     FROM tb_sensor 
     ORDER BY id DESC 
     LIMIT 5"
);

if (!$query) {
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'error' => 'Query failed',
        'message' => mysqli_error($konek)
    ]));
}

$data = [
    'labels' => [],
    'suhu' => [],
    'kelembaban' => []
];

while ($row = mysqli_fetch_assoc($query)) {
    $data['labels'][] = $row['waktu'];
    $data['suhu'][] = (float)$row['suhu'];
    $data['kelembaban'][] = (float)$row['kelembaban'];
}

// Reverse for chronological order
$data['labels'] = array_reverse($data['labels']);
$data['suhu'] = array_reverse($data['suhu']);
$data['kelembaban'] = array_reverse($data['kelembaban']);

mysqli_close($konek);

// Add system status
$response = [
    'status' => 'success',
    'data' => $data,
    'timestamp' => date('Y-m-d H:i:s'),
    'system' => [
        'memory_usage' => memory_get_usage(),
        'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]
    ]
];

echo json_encode($response);
?>