<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../models/db.php";
include __DIR__ . "/../models/addRoomType.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $guest    = $_POST["guests"] ?? '';
    $checkin  = trim($_POST["checkIn"] ?? '');
    $checkout = trim($_POST["checkOut"] ?? '');

    // Guests must be a positive whole number; default to 1 if omitted/invalid.
    $guest = ctype_digit((string) $guest) && (int) $guest > 0 ? (int) $guest : 1;

    $database = new db();
    $connection = $database->connection();

    $result = search_room($connection, $guest);

    $_SESSION["rooms"] = $result ?: [];

    // remember the searched dates so the booking page can pre-fill them
    $_SESSION["search_checkin"]  = $checkin;
    $_SESSION["search_checkout"] = $checkout;
}
