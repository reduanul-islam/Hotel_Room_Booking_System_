<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../models/db.php";
include __DIR__ . "/../models/BookingModel.php";
include __DIR__ . "/../models/addRoomType.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/auth/login.php");
    exit;
}

$database = new db();
$conn = $database->connection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id      = $_SESSION['user_id'];
    $room_type_id = trim($_POST['room_type_id'] ?? '');
    $checkin      = trim($_POST['checkIn'] ?? '');
    $checkout     = trim($_POST['checkOut'] ?? '');

    $dateFormat = '/^\d{4}-\d{2}-\d{2}$/';

    if (empty($room_type_id) || empty($checkin) || empty($checkout)
        || !ctype_digit($room_type_id)
        || !preg_match($dateFormat, $checkin) || !preg_match($dateFormat, $checkout)) {
        $_SESSION['booking_error'] = "Please provide a valid room type and dates.";
        header("Location: ../views/guest/book.php?room_type_id=" . urlencode($room_type_id));
        exit;
    }

    if ($checkin < date('Y-m-d')) {
        $_SESSION['booking_error'] = "Check-in date cannot be in the past.";
        header("Location: ../views/guest/book.php?room_type_id=" . urlencode($room_type_id));
        exit;
    }

    if ($checkin >= $checkout) {
        $_SESSION['booking_error'] = "Checkout date must be after checkin date.";
        header("Location: ../views/guest/book.php?room_type_id=" . urlencode($room_type_id));
        exit;
    }

    $roomType = get_room_type_by_id($conn, $room_type_id);

    if (!$roomType) {
        $_SESSION['booking_error'] = "Selected room type could not be found.";
        header("Location: ../views/guest/search_room.php");
        exit;
    }

    // Recalculate the total server-side (never trust a client-submitted price)
    $nights = (strtotime($checkout) - strtotime($checkin)) / 86400;
    $total  = $nights * $roomType['price_per_night'];

    $room = findAvailableRoom($conn, $room_type_id, $checkin, $checkout);

    if (!$room) {
        $_SESSION['booking_error'] = "Sorry, no {$roomType['name']} rooms are available for those dates.";
        header("Location: ../views/guest/book.php?room_type_id=" . urlencode($room_type_id));
        exit;
    }

    $result = insertBooking($conn, $user_id, $room['id'], $checkin, $checkout, $total);

    if ($result) {
        $_SESSION['booking_success'] = "Booking confirmed! Room {$room['room_number']} ({$roomType['name']}) from {$checkin} to {$checkout}.";
        header("Location: ../views/guest/mybooking.php");
        exit;
    } else {
        $_SESSION['booking_error'] = "Booking failed. Please try again.";
        header("Location: ../views/guest/book.php?room_type_id=" . urlencode($room_type_id));
        exit;
    }
}
?>
