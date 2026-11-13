<?php

function getUpcomingBooking($connection, $userId){

    $sql = "SELECT b.*, r.room_number, rt.name AS room_type_name
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            JOIN room_types rt ON rt.id = r.room_type_id
            WHERE b.user_id = ?
            AND b.checkin_date >= CURDATE()
            AND b.status != 'Cancelled'
            AND b.status != 'Checked-Out'
            ORDER BY b.checkin_date ASC
            LIMIT 1";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

// Returns ALL bookings for a user, most recent check-in first.
function getUserBookings($connection, $userId){

    $sql = "SELECT b.id, b.checkin_date, b.checkout_date, b.total_price, b.status,
                   r.room_number, rt.name AS room_type_name
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            JOIN room_types rt ON rt.id = r.room_type_id
            WHERE b.user_id = ?
            ORDER BY b.checkin_date DESC";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Finds a physical room of the given room type that has no overlapping
// booking for the requested date range. Returns the room row, or null.
function findAvailableRoom($connection, $roomTypeId, $checkin, $checkout){

    $sql = "SELECT r.id, r.room_number
            FROM rooms r
            WHERE r.room_type_id = ?
            AND r.id NOT IN (
                SELECT b.room_id FROM bookings b
                WHERE b.status != 'Cancelled'
                AND b.checkin_date < ?
                AND b.checkout_date > ?
            )
            LIMIT 1";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param("iss", $roomTypeId, $checkout, $checkin);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

function insertBooking($conn, $user_id, $room_id, $checkin, $checkout, $total){

    $sql = "INSERT INTO bookings (user_id, room_id, checkin_date, checkout_date, total_price)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissd", $user_id, $room_id, $checkin, $checkout, $total);

    return $stmt->execute();
}

// Cancels a booking, but only if it belongs to the given user
// (prevents one guest from cancelling someone else's booking).
function cancelBooking($connection, $bookingId, $userId){

    $sql = "UPDATE bookings SET status = 'Cancelled'
            WHERE id = ? AND user_id = ? AND status != 'Checked-Out'";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ii", $bookingId, $userId);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// ── Admin-facing helpers ─────────────────────────────────────────

// Guests checking in today, still in 'Booked' status.
function getTodayArrivals($connection){
    $sql = "SELECT b.id, u.name AS guest_name, u.special_requests, r.room_number, rt.name AS room_type_name
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            JOIN rooms r ON r.id = b.room_id
            JOIN room_types rt ON rt.id = r.room_type_id
            WHERE b.checkin_date = CURDATE() AND b.status = 'Booked'
            ORDER BY b.id";
    $result = $connection->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Guests checking out today, currently 'Checked-In'.
function getTodayDepartures($connection){
    $sql = "SELECT b.id, u.name AS guest_name, r.room_number, rt.name AS room_type_name
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            JOIN rooms r ON r.id = b.room_id
            JOIN room_types rt ON rt.id = r.room_type_id
            WHERE b.checkout_date = CURDATE() AND b.status = 'Checked-In'
            ORDER BY b.id";
    $result = $connection->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Revenue total for each of the last 8 weeks (oldest first), based on
// check-in date of non-cancelled bookings. Used for the dashboard chart.
function getWeeklyRevenue($connection){
    $sql = "SELECT YEARWEEK(checkin_date, 1) AS yw, SUM(total_price) AS revenue
            FROM bookings
            WHERE status != 'Cancelled'
              AND checkin_date >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
            GROUP BY yw";
    $result = $connection->query($sql);
    $byWeek = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $byWeek[$row['yw']] = (float) $row['revenue'];
        }
    }

    $labels = [];
    $data = [];
    for ($i = 7; $i >= 0; $i--) {
        $ts = strtotime("-{$i} week");
        $yw = (int) date('oW', $ts); // ISO year + week, matches YEARWEEK(...,1)
        $labels[] = 'Wk ' . date('W', $ts);
        $data[] = $byWeek[$yw] ?? 0;
    }

    return ['labels' => $labels, 'data' => $data];
}

// All bookings, most recent first, optionally filtered by status/date range.
function getAllBookings($connection, $status = '', $dateFrom = '', $dateTo = ''){
    $sql = "SELECT b.id, u.name AS guest_name, r.room_number, rt.name AS room_type_name,
                   b.checkin_date, b.checkout_date, b.total_price, b.status
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            JOIN rooms r ON r.id = b.room_id
            JOIN room_types rt ON rt.id = r.room_type_id
            WHERE 1=1";
    $types = "";
    $params = [];

    if ($status !== '') {
        $sql .= " AND b.status = ?";
        $types .= "s";
        $params[] = $status;
    }
    if ($dateFrom !== '') {
        $sql .= " AND b.checkin_date >= ?";
        $types .= "s";
        $params[] = $dateFrom;
    }
    if ($dateTo !== '') {
        $sql .= " AND b.checkin_date <= ?";
        $types .= "s";
        $params[] = $dateTo;
    }

    $sql .= " ORDER BY b.checkin_date DESC, b.id DESC";

    $stmt = $connection->prepare($sql);
    if (!$stmt) return [];

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Marks a 'Booked' booking as 'Checked-In'.
function checkInBooking($connection, $bookingId){
    $sql = "UPDATE bookings SET status = 'Checked-In' WHERE id = ? AND status = 'Booked'";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $bookingId);
    return $stmt->execute() && $stmt->affected_rows > 0;
}

// Marks a 'Checked-In' booking as 'Checked-Out'.
function checkOutBooking($connection, $bookingId){
    $sql = "UPDATE bookings SET status = 'Checked-Out' WHERE id = ? AND status = 'Checked-In'";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $bookingId);
    return $stmt->execute() && $stmt->affected_rows > 0;
}
?>
