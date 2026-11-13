<?php

function room_number_exists($connection, $roomNumber, $excludeId = null) {
    if ($excludeId) {
        $sql = "SELECT id FROM rooms WHERE room_number = ? AND id != ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("si", $roomNumber, $excludeId);
    } else {
        $sql = "SELECT id FROM rooms WHERE room_number = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $roomNumber);
    }
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function add_room($connection, $roomNumber, $floor, $roomTypeId, $status) {
    $sql = "INSERT INTO rooms (room_number, floor, room_type_id, status) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("siis", $roomNumber, $floor, $roomTypeId, $status);
    return $stmt->execute();
}

function update_room($connection, $id, $roomNumber, $floor, $roomTypeId, $status) {
    $sql = "UPDATE rooms SET room_number = ?, floor = ?, room_type_id = ?, status = ? WHERE id = ?";
    $stmt = $connection->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("siisi", $roomNumber, $floor, $roomTypeId, $status, $id);
    return $stmt->execute();
}

// Deletes a room, but only if it has no active (non finished) booking.
function delete_room($connection, $id) {
    $sql = "SELECT COUNT(*) AS cnt FROM bookings WHERE room_id = ? AND status IN ('Booked','Checked-In')";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && $row['cnt'] > 0) {
        return false;
    }

    $sql = "DELETE FROM rooms WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function get_room_by_id($connection, $id) {
    $sql = "SELECT r.*, rt.name AS room_type_name
            FROM rooms r JOIN room_types rt ON rt.id = r.room_type_id
            WHERE r.id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Returns every room with its type name and a computed occupancy status:
// 'maintenance' (manually flagged), 'booked' (has an active stay right now),
// or 'available'.
function get_all_rooms_with_status($connection) {
    $sql = "SELECT r.id, r.room_number, r.floor, r.status AS room_status,
                   rt.id AS room_type_id, rt.name AS room_type_name,
                   EXISTS (
                       SELECT 1 FROM bookings b
                       WHERE b.room_id = r.id
                         AND b.status IN ('Booked','Checked-In')
                         AND b.checkin_date <= CURDATE()
                         AND b.checkout_date > CURDATE()
                   ) AS is_occupied
            FROM rooms r
            JOIN room_types rt ON rt.id = r.room_type_id
            ORDER BY r.floor, r.room_number";
    $result = $connection->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function toggle_room_maintenance($connection, $id) {
    $room = get_room_by_id($connection, $id);
    if (!$room) return null;

    $newStatus = $room['status'] === 'maintenance' ? 'available' : 'maintenance';

    $sql = "UPDATE rooms SET status = ? WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("si", $newStatus, $id);

    if ($stmt->execute()) {
        return $newStatus;
    }
    return null;
}

function count_rooms_by_occupancy($connection) {
    $sql = "SELECT
                COUNT(*) AS total,
                SUM(status = 'maintenance') AS maintenance,
                SUM(status = 'available' AND EXISTS (
                    SELECT 1 FROM bookings b
                    WHERE b.room_id = rooms.id
                      AND b.status IN ('Booked','Checked-In')
                      AND b.checkin_date <= CURDATE()
                      AND b.checkout_date > CURDATE()
                )) AS occupied
            FROM rooms";
    $result = $connection->query($sql);
    $row = $result ? $result->fetch_assoc() : ['total' => 0, 'maintenance' => 0, 'occupied' => 0];

    $total       = (int) ($row['total'] ?? 0);
    $maintenance = (int) ($row['maintenance'] ?? 0);
    $occupied    = (int) ($row['occupied'] ?? 0);

    return [
        'total'       => $total,
        'occupied'    => $occupied,
        'maintenance' => $maintenance,
        'available'   => max(0, $total - $occupied - $maintenance),
    ];
}
