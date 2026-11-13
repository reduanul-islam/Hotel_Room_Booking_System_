<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../models/db.php";
include __DIR__ . "/../models/UserModel.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $uemail = trim($_POST["email"] ?? '');
    $upass  = $_POST["password"] ?? '';
    $remember_me = isset($_POST["remember_me"]);

    if ($uemail === '' || $upass === '') {
        $_SESSION["error"] = "Please enter both email and password.";
        header("Location: ../views/auth/login.php");
        exit;
    }

    $database = new db();
    $connection = $database->connection();

    $user = loginUser($connection, $uemail);

    if ($user && password_verify($upass, $user['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user_id']     = $user['id'];
        $_SESSION['name']        = $user['name'];
        $_SESSION['role']        = $user['role'];
        $_SESSION['phone']       = $user['phone'];
        $_SESSION['email']       = $user['email'];
        $_SESSION['nationality'] = $user['nationality'];
        $_SESSION['preferred_room_type_id'] = $user['preferred_room_type_id'];
        $_SESSION['special_requests']       = $user['special_requests'];

        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            setcookie("remember_token", $token, time() + (86400 * 30), "/", "", false, true);
            saveRememberToken($connection, $user['id'], $token);
        }

        if ($user['role'] == "admin") {
            header('Location: ../views/admin.php');
            exit;
        } else {
            header('Location: ../views/user.php');
            exit;
        }
    } else {
        $_SESSION["error"] = "Invalid email or password.";
        header("Location: ../views/auth/login.php");
        exit;
    }
}
