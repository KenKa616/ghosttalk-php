<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Data Directory
$dataDir = __DIR__ . '/data/';
if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);

// Helper Functions
function readData($file) {
    global $dataDir;
    $path = $dataDir . $file;
    if (!file_exists($path)) return [];
    $json = file_get_contents($path);
    return json_decode($json, true) ?? [];
}

function writeData($file, $data) {
    global $dataDir;
    $path = $dataDir . $file;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

function generateId() {
    return bin2hex(random_bytes(8));
}

function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}
