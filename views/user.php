<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../models/db.php";
require_once __DIR__ . "/../models/BookingModel.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// Admins have their own dashboard.
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin.php");
    exit;
}

$database = new db();
$connection = $database->connection();

$booking = getUpcomingBooking($connection, $_SESSION['user_id']);
$subscribeOffers = isset($_COOKIE['subscribe_offers']) && $_COOKIE['subscribe_offers'] === '1';
$roomTypes = $connection->query("SELECT id,name FROM room_types ORDER BY name");

$prefStmt = $connection->prepare(
    "SELECT rt.name AS preferred_room_type_name, u.special_requests
     FROM users u LEFT JOIN room_types rt ON rt.id = u.preferred_room_type_id
     WHERE u.id = ?"
);
$prefStmt->bind_param("i", $_SESSION['user_id']);
$prefStmt->execute();
$currentPrefs = $prefStmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile — Hotel Booking</title>
    <link rel="stylesheet" href="../assets/user.css">
</head>

<body>

<div class="page-wrapper">

<div class="sidebar">
<p class="panel-brand">***** GRAND PALACE</p>

<h3 class="panel-heading">Guest<br>Portal</h3>

<nav class="sidebar-nav">
<a href="user.php" class="nav-item active">My Profile</a>
<a href="guest/mybooking.php" class="nav-item">My Bookings</a>
<a href="guest/search_room.php" class="nav-item">Search Rooms</a>
<a href="../controllers/logoutController.php" class="nav-item logout">Logout</a>
</nav>
</div>

<div class="main-content">

<h2>My Profile</h2>
<p class="subtitle">Manage your account and preferences</p>

<div class="booking-card">
<div class="booking-card-inner">

<?php if($booking){ ?>

<div class="booking-left">
<p class="booking-label">Upcoming Stay</p>

<p class="booking-room">
<?= htmlspecialchars($booking['room_type_name']) ?> — Room <?= htmlspecialchars($booking['room_number']) ?>
</p>

<p class="booking-dates">
<?= date('d M Y', strtotime($booking['checkin_date'])) ?>
→
<?= date('d M Y', strtotime($booking['checkout_date'])) ?>
</p>
</div>

<div class="booking-right">
<span class="status-badge"><?= htmlspecialchars($booking['status']) ?></span>
<p class="booking-id">Booking #<?= htmlspecialchars($booking['id']) ?></p>
</div>

<?php } else { ?>

<p style="margin:0;color:#888;">No upcoming stays.</p>

<?php } ?>

</div>
</div>

<div class="form-grid">

<div class="form-box">

<h3 class="form-box-title">Edit Profile</h3>

<form method="POST" action="../controllers/profileController.php">

<label for="name">Full Name</label>
<input type="text" id="name" name="name" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>">
<span class="error-message" id="nameError"></span>

<label for="email">Email Address</label>
<input type="email" id="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>">
<span class="error-message" id="emailError"></span>

<label for="phone">Phone Number</label>
<input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">
<span class="error-message" id="phoneError"></span>

<label for="nationality">Nationality</label>

<select id="nationality" name="nationality">

<?php
$countries = ["Bangladeshi","Indian","Pakistani","American","British","Canadian","Australian","Other"];

foreach($countries as $country){
    $selected = ($_SESSION['nationality'] ?? '') == $country ? 'selected' : '';
    echo "<option value='$country' $selected>$country</option>";
}
?>

</select>

<span class="error-message" id="nationalityError"></span>

<button type="submit" name="update_profile">Save Changes</button>

</form>
</div>

<div class="form-box">

<h3 class="form-box-title">Preferences</h3>

<div id="prefSummary" class="pref-summary">
<?php if ($currentPrefs && ($currentPrefs['preferred_room_type_name'] || $currentPrefs['special_requests'])): ?>
    <div class="pref-summary-icon">&#10003;</div>
    <div class="pref-summary-text">
        <div class="pref-summary-label">Currently Saved</div>
        <div class="pref-summary-value">
            <span id="prefSummaryRoomType"><?= $currentPrefs['preferred_room_type_name'] ? htmlspecialchars($currentPrefs['preferred_room_type_name']) . ' room preferred' : 'No room type preference set' ?></span><?php if ($currentPrefs['special_requests']): ?> &middot; <span id="prefSummaryRequests"><?= htmlspecialchars($currentPrefs['special_requests']) ?></span><?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="pref-summary-text pref-summary-empty">No preferences saved yet — set them below.</div>
<?php endif; ?>
</div>

<form method="POST" action="../controllers/preferenceController.php">

<label for="preferred_room_type_id">Preferred Room Type</label>

<select id="preferred_room_type_id" name="preferred_room_type_id">

<option value="">— Select room type —</option>

<?php while($rt = $roomTypes->fetch_assoc()){ ?>

<option value="<?= $rt['id'] ?>" <?= (($_SESSION['preferred_room_type_id'] ?? '') == $rt['id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($rt['name']) ?>
</option>

<?php } ?>

</select>

<span class="error-message" id="roomTypeError"></span>

<label for="special_requests">Special Requests</label>

<textarea id="special_requests" name="special_requests" rows="5" placeholder="e.g. High floor, extra pillows, early check-in..."><?= htmlspecialchars($_SESSION['special_requests'] ?? '') ?></textarea>

<div class="checkbox-group">
<input type="checkbox" id="subscribe_offers" name="subscribe_offers" value="1" <?= $subscribeOffers ? 'checked' : '' ?>>
<label for="subscribe_offers">Subscribe to offers & deals</label>
</div>

<button type="submit" name="update_preferences">Save Preferences</button>

</form>
</div>

</div>
</div>
</div>

<script src="../ajax/user_profile.js"></script>

</body>
</html>