<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../models/db.php";
include __DIR__ . "/../models/BookingModel.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $database = new db();
    $connection = $database->connection();

    $bookingId = trim($_POST['booking_id'] ?? '');
    $userId = $_SESSION['user_id'];

    if ($bookingId !== '' && ctype_digit($bookingId) && cancelBooking($connection, (int) $bookingId, $userId)) {
        $_SESSION['cancel_message'] = "Booking #{$bookingId} has been cancelled.";
    } else {
        $_SESSION['cancel_message'] = "Could not cancel that booking. It may already be checked out or does not belong to you.";
    }
}

header("Location: ../views/guest/mybooking.php");
exit;
?>
