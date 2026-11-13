<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

include __DIR__ . "/../models/db.php";
include __DIR__ . "/../models/RoomModel.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authorized.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$roomId = (int) ($input['room_id'] ?? 0);

if ($roomId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid room id.']);
    exit;
}

$database = new db();
$connection = $database->connection();

$newStatus = toggle_room_maintenance($connection, $roomId);

if ($newStatus !== null) {
    echo json_encode(['success' => true, 'new_status' => $newStatus]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not update this room.']);
}
