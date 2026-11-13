<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

include __DIR__ . "/../models/db.php";
include __DIR__ . "/../models/BookingModel.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authorized.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bookingId = (int) ($input['booking_id'] ?? 0);

if ($bookingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking id.']);
    exit;
}

$database = new db();
$connection = $database->connection();

if (checkInBooking($connection, $bookingId)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not check in this booking.']);
}
