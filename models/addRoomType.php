<?php

function add_roomType($connection, $name, $description, $price_per_night, $max_capacity, $thumbnail_path, $amenities) {

    $sql = "INSERT INTO room_types(name,description,price_per_night,max_capacity,thumbnail_path,amenities) VALUES(?,?,?,?,?,?)";

    $stmt = $connection->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ssdiss", $name, $description, $price_per_night, $max_capacity, $thumbnail_path, $amenities);

    return $stmt->execute();
}

function update_roomType($connection, $id, $name, $description, $price_per_night, $max_capacity, $amenities, $thumbnail_path = null) {

    if ($thumbnail_path !== null) {
        $sql = "UPDATE room_types
                SET name = ?, description = ?, price_per_night = ?, max_capacity = ?, amenities = ?, thumbnail_path = ?
                WHERE id = ?";
        $stmt = $connection->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("ssdissi", $name, $description, $price_per_night, $max_capacity, $amenities, $thumbnail_path, $id);
    } else {
        $sql = "UPDATE room_types
                SET name = ?, description = ?, price_per_night = ?, max_capacity = ?, amenities = ?
                WHERE id = ?";
        $stmt = $connection->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("ssdisi", $name, $description, $price_per_night, $max_capacity, $amenities, $id);
    }

    return $stmt->execute();
}

// Deletes a room type. Fails (returns false) if rooms still reference it,
// or if any booking made against one of its rooms is not yet finished,
// so historical/active bookings are never silently orphaned.
function delete_roomType($connection, $id) {

    $sql = "SELECT COUNT(*) AS cnt
            FROM rooms r
            JOIN bookings b ON b.room_id = r.id
            WHERE r.room_type_id = ? AND b.status IN ('Booked','Checked-In')";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && $row['cnt'] > 0) {
        return false;
    }

    $sql = "DELETE FROM room_types WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $id);

    return $stmt->execute();
}

function get_room_type_by_id($connection, $id) {

    $sql = "SELECT id,name,description,price_per_night,max_capacity,thumbnail_path,amenities
            FROM room_types WHERE id = ?";

    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

// Returns every room type, ordered by name, for admin listings and dropdowns.
function get_all_room_types($connection) {

    $sql = "SELECT id,name,description,price_per_night,max_capacity,thumbnail_path,amenities
            FROM room_types ORDER BY name";

    $result = $connection->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function search_room($connection, $guest) {

    $sql = "SELECT id,name,description,price_per_night,max_capacity,thumbnail_path,amenities FROM room_types WHERE max_capacity >= ?";

    $stmt = $connection->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $guest);

    $stmt->execute();

    $result = $stmt->get_result();

    $rooms = [];

    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }

    return $rooms;
}
