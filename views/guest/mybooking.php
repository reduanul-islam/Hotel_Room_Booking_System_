<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../../models/db.php";
include __DIR__ . "/../../models/BookingModel.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$database = new db();
$connection = $database->connection();

$bookings = getUserBookings($connection, $_SESSION['user_id']);

$cancelMessage = $_SESSION['cancel_message'] ?? '';
unset($_SESSION['cancel_message']);

function statusBadgeClass($status) {
    switch ($status) {
        case 'Booked':      return 'badge-confirmed';
        case 'Checked-In':  return 'badge-checkedin';
        case 'Checked-Out': return 'badge-checkedout';
        case 'Cancelled':   return 'badge-cancelled';
        default:            return 'badge-pending';
    }
}
?>
<!DOCTYPE html>
<html>

    <head>
        <title> All my Booking Rooms  </title>
        <link rel="stylesheet" href="../../assets/mybooking.css">
    </head>

    <body>

        <div class="page-wrapper">


        <div class="sidebar">

            <div class="panel-brand">HOTEL SYSTEM</div>

            <div class="panel-heading">
                My <br> Bookings
            </div>

            <div class="sidebar-nav">
                <a href="../user.php" class="nav-item">My Profile</a>
                <a href="mybooking.php" class="nav-item active">My Bookings</a>
                <a href="search_room.php" class="nav-item">Search Rooms</a>
                <a href="../../controllers/logoutController.php" class="nav-item logout">Logout</a>
            </div>

        </div>


    <div class="main-content">
    <h2>My Bookings</h2>
    <p>All my reservations</p>

    <?php if ($cancelMessage): ?>
        <p style="color:#0f5132;background:#d1e7dd;padding:10px 16px;border-radius:8px;">
            <?= htmlspecialchars($cancelMessage) ?>
        </p>
    <?php endif; ?>

    <div class="form-box">

    <table class="bookings-table">
        <tr>
            <th>Id</th>
            <th>Room Type</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Total</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if (count($bookings) === 0): ?>
        <tr>
            <td colspan="7" style="text-align:center;color:#888;">
                You have no bookings yet. <a href="search_room.php">Search rooms</a> to make one.
            </td>
        </tr>
        <?php else: foreach ($bookings as $b): ?>
        <tr>
            <td><label><?= htmlspecialchars($b['id']) ?></label></td>
            <td><label><?= htmlspecialchars($b['room_type_name']) ?> (Room <?= htmlspecialchars($b['room_number']) ?>)</label></td>
            <td><label><?= date('d M Y', strtotime($b['checkin_date'])) ?></label></td>
            <td><label><?= date('d M Y', strtotime($b['checkout_date'])) ?></label></td>
            <td><label>Tk <?= number_format($b['total_price'], 2) ?></label></td>
            <td><span class="badge <?= statusBadgeClass($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
            <td>
                <?php if ($b['status'] === 'Booked'): ?>
                <form method="POST" action="../../controllers/cancelBookingController.php"
                      onsubmit="return confirm('Cancel this booking?');" style="margin:0;">
                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($b['id']) ?>">
                    <button class="cancel-btn" type="submit">Cancel</button>
                </form>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>

    </table>

    </div>

    </div>

    </div>

    </body>
</html>
