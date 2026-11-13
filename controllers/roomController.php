<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../models/db.php";
include __DIR__ . "/../models/RoomModel.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../views/auth/login.php");
    exit();
}

$database = new db();
$connection = $database->connection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? (isset($_POST['add_room']) ? 'add' : (isset($_POST['update_room']) ? 'edit' : ''));

    if ($action === 'add' || $action === 'edit') {

        $id           = (int) ($_POST['id'] ?? 0);
        $room_number  = trim($_POST['room_number'] ?? '');
        $floor        = $_POST['floor'] ?? '';
        $room_type_id = $_POST['room_type_id'] ?? '';
        $status       = $_POST['status'] ?? 'available';

        $errors = [];

        if ($room_number === '') {
            $errors[] = "Room number is required.";
        } elseif (room_number_exists($connection, $room_number, $action === 'edit' ? $id : null)) {
            $errors[] = "That room number already exists.";
        }
        if (!ctype_digit((string) $floor) || (int) $floor < 1) {
            $errors[] = "Floor must be a positive whole number.";
        }
        if (!ctype_digit((string) $room_type_id) || (int) $room_type_id < 1) {
            $errors[] = "Please select a room type.";
        }
        if (!in_array($status, ['available', 'maintenance'], true)) {
            $errors[] = "Invalid status.";
        }
        if ($action === 'edit' && $id <= 0) {
            $errors[] = "Invalid room.";
        }

        if (!empty($errors)) {
            $_SESSION['flash_errors'] = $errors;
        } elseif ($action === 'add') {
            $success = add_room($connection, $room_number, (int) $floor, (int) $room_type_id, $status);
            $_SESSION[$success ? 'flash_success' : 'flash_error'] = $success ? "Room added successfully." : "Failed to add room.";
        } else {
            $success = update_room($connection, $id, $room_number, (int) $floor, (int) $room_type_id, $status);
            $_SESSION[$success ? 'flash_success' : 'flash_error'] = $success ? "Room updated successfully." : "Failed to update room.";
        }

    } elseif ($action === 'delete') {

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && delete_room($connection, $id)) {
            $_SESSION['flash_success'] = "Room deleted.";
        } else {
            $_SESSION['flash_error'] = "Could not delete this room — it may have an active booking.";
        }
    }

    header("Location: ../views/admin.php#rooms");
    exit();
}
