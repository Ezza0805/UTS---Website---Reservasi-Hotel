<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$hotelId = $_GET['hotel_id'] ?? '';

if (!ctype_digit($hotelId)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        id,
        name
    FROM room_types
    WHERE hotel_id = ?
    ORDER BY name ASC
");

$stmt->execute([$hotelId]);

$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($roomTypes);