<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../models/db.php";
include __DIR__ . "/../models/addRoomType.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../views/auth/login.php");
    exit();
}

$database = new db();
$connection = $database->connection();
$config = require __DIR__ . "/../config/config.php";

function handle_thumbnail_upload($connection, $config, &$errorMsg) {
    if (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // no new file supplied
    }

    if ($_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "Thumbnail upload failed. Please try again.";
        return false;
    }

    if ($_FILES['thumbnail']['size'] > $config['max_upload_size']) {
        $errorMsg = "Thumbnail must be smaller than 2MB.";
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['thumbnail']['tmp_name']);
    finfo_close($finfo);

    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

    if (!in_array($mime, $config['allowed_image_types'], true) || !isset($extensions[$mime])) {
        $errorMsg = "Thumbnail must be a JPEG or PNG image.";
        return false;
    }

    $upload_folder = __DIR__ . "/../assets/uploads/room_types/";
    if (!is_dir($upload_folder)) {
        mkdir($upload_folder, 0755, true);
    }

    $filename = "roomtype_" . time() . "_" . bin2hex(random_bytes(6)) . "." . $extensions[$mime];

    if (!move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_folder . $filename)) {
        $errorMsg = "Could not save the uploaded thumbnail.";
        return false;
    }

    return "assets/uploads/room_types/" . $filename;
}

function validated_room_type_fields($errors) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_per_night = $_POST['price_per_night'] ?? '';
    $max_capacity = $_POST['max_capacity'] ?? '';

    if ($name === '' || !in_array($name, ['Standard', 'Deluxe', 'Suite'], true)) {
        $errors[] = "Please select a valid room type name.";
    }
    if (!is_numeric($price_per_night) || (float) $price_per_night <= 0) {
        $errors[] = "Price per night must be a positive number.";
    }
    if (!ctype_digit((string) $max_capacity) || (int) $max_capacity < 1 || (int) $max_capacity > 10) {
        $errors[] = "Max capacity must be a whole number between 1 and 10.";
    }

    $amenities = "";
    if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
        $amenities = implode(",", array_map('trim', $_POST['amenities']));
    }

    return [
        'name' => $name,
        'description' => $description,
        'price_per_night' => (float) $price_per_night,
        'max_capacity' => (int) $max_capacity,
        'amenities' => $amenities,
        'errors' => $errors,
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? (isset($_POST['add_room_type']) ? 'add' : (isset($_POST['update_room_type']) ? 'edit' : ''));

    if ($action === 'add') {

        $f = validated_room_type_fields([]);

        $thumbErr = null;
        $thumbnail_path = handle_thumbnail_upload($connection, $config, $thumbErr);
        if ($thumbErr) $f['errors'][] = $thumbErr;

        if (!empty($f['errors'])) {
            $_SESSION['flash_errors'] = $f['errors'];
        } else {
            $success = add_roomType($connection, $f['name'], $f['description'], $f['price_per_night'], $f['max_capacity'], $thumbnail_path ?? '', $f['amenities']);
            $_SESSION[$success ? 'flash_success' : 'flash_error'] = $success ? "Room type added successfully." : "Failed to add room type.";
        }

    } elseif ($action === 'edit') {

        $id = (int) ($_POST['id'] ?? 0);
        $f = validated_room_type_fields([]);
        if ($id <= 0) $f['errors'][] = "Invalid room type.";

        $thumbErr = null;
        $thumbnail_path = handle_thumbnail_upload($connection, $config, $thumbErr);
        if ($thumbErr) $f['errors'][] = $thumbErr;

        if (!empty($f['errors'])) {
            $_SESSION['flash_errors'] = $f['errors'];
        } else {
            $success = update_roomType($connection, $id, $f['name'], $f['description'], $f['price_per_night'], $f['max_capacity'], $f['amenities'], $thumbnail_path);
            $_SESSION[$success ? 'flash_success' : 'flash_error'] = $success ? "Room type updated successfully." : "Failed to update room type.";
        }

    } elseif ($action === 'delete') {

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && delete_roomType($connection, $id)) {
            $_SESSION['flash_success'] = "Room type deleted.";
        } else {
            $_SESSION['flash_error'] = "Could not delete this room type — it may still have active bookings.";
        }
    }

    header("Location: ../views/admin.php#room-types");
    exit();
}
