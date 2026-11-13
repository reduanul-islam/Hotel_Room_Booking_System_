<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: user.php");
    exit();
}

require_once __DIR__ . "/../models/db.php";
require_once __DIR__ . "/../models/BookingModel.php";
require_once __DIR__ . "/../models/RoomModel.php";
require_once __DIR__ . "/../models/addRoomType.php";

$database = new db();
$connection = $database->connection();

$roomStats     = count_rooms_by_occupancy($connection);
$arrivals      = getTodayArrivals($connection);
$departures    = getTodayDepartures($connection);
$revenueChart  = getWeeklyRevenue($connection);
$roomTypes     = get_all_room_types($connection);
$rooms         = get_all_rooms_with_status($connection);

// Editing an existing room type? Pre-fill the Add form with its data.
$editingRoomType = null;
if (isset($_GET['edit_room_type']) && ctype_digit((string) $_GET['edit_room_type'])) {
    $editingRoomType = get_room_type_by_id($connection, (int) $_GET['edit_room_type']);
}

// Editing an existing room?
$editingRoom = null;
if (isset($_GET['edit_room']) && ctype_digit((string) $_GET['edit_room'])) {
    $editingRoom = get_room_by_id($connection, (int) $_GET['edit_room']);
}

// Bookings tab filters
$filterStatus   = $_GET['status'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to'] ?? '';
$allBookings    = getAllBookings($connection, $filterStatus, $filterDateFrom, $filterDateTo);

function amenityTags($amenitiesCsv) {
    $out = '';
    foreach (array_filter(array_map('trim', explode(',', $amenitiesCsv))) as $a) {
        $out .= '<span class="tag">' . htmlspecialchars($a) . '</span>';
    }
    return $out;
}

function bookingStatusBadgeClass($status) {
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
    <title>Admin Dashboard — Grand Palace</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<div class="page-wrapper">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <p class="panel-brand">***** GRAND PALACE</p>
        <h3 class="panel-heading">Admin<br>Panel</h3>
        <nav class="sidebar-nav">
            <a href="#dashboard" class="nav-item active" onclick="showSection('dashboard', this)">Dashboard</a>
            <a href="#room-types" class="nav-item" onclick="showSection('room-types', this)">Room Types</a>
            <a href="#rooms" class="nav-item" onclick="showSection('rooms', this)">Rooms</a>
            <a href="#bookings" class="nav-item" onclick="showSection('bookings', this)">Bookings</a>
            <a href="../controllers/logoutController.php" class="nav-item logout">Logout</a>
        </nav>
    </div>

    <!-- MAIN -->
    <div class="main-content">

        <!-- ── Flash Messages ── -->
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div style="background:#d1e7dd;color:#0f5132;padding:12px 20px;border-radius:8px;margin-bottom:16px;">
                <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
        <?php unset($_SESSION['flash_success']); endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div style="background:#f8d7da;color:#842029;padding:12px 20px;border-radius:8px;margin-bottom:16px;">
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
        <?php unset($_SESSION['flash_error']); endif; ?>

        <?php if (!empty($_SESSION['flash_errors'])): ?>
            <div style="background:#f8d7da;color:#842029;padding:12px 20px;border-radius:8px;margin-bottom:16px;">
                <strong>Please fix the following:</strong>
                <ul style="margin-top:6px;padding-left:18px;">
                    <?php foreach ($_SESSION['flash_errors'] as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php unset($_SESSION['flash_errors']); endif; ?>

        <!-- ═══════════════════════════
             SECTION 1: DASHBOARD
        ════════════════════════════ -->
        <div id="section-dashboard" class="section">

            <h2>Dashboard</h2>
            <p class="subtitle">Today's overview</p>

            <!-- Summary Cards -->
            <div class="summary-grid">
                <div class="summary-card">
                    <p class="summary-label">Total Rooms</p>
                    <p class="summary-number"><?= (int) $roomStats['total'] ?></p>
                </div>
                <div class="summary-card">
                    <p class="summary-label">Occupied</p>
                    <p class="summary-number" style="color:#1a3a6b;"><?= (int) $roomStats['occupied'] ?></p>
                </div>
                <div class="summary-card">
                    <p class="summary-label">Available</p>
                    <p class="summary-number" style="color:#2e7d32;"><?= (int) $roomStats['available'] ?></p>
                </div>
                <div class="summary-card">
                    <p class="summary-label">Maintenance</p>
                    <p class="summary-number" style="color:#b71c1c;"><?= (int) $roomStats['maintenance'] ?></p>
                </div>
            </div>

            <div class="two-col">

                <!-- Today's Arrivals -->
                <div class="form-box">
                    <h3 class="form-box-title">Today's Arrivals</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Special Requests</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($arrivals)): ?>
                            <tr><td colspan="5" style="text-align:center;color:#888;">No arrivals today.</td></tr>
                            <?php else: foreach ($arrivals as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['guest_name']) ?></td>
                                <td><?= htmlspecialchars($a['room_number']) ?></td>
                                <td><?= htmlspecialchars($a['room_type_name']) ?></td>
                                <td><?= $a['special_requests'] ? htmlspecialchars($a['special_requests']) : '<span style="color:#aaa;">—</span>' ?></td>
                                <td>
                                    <button class="action-btn checkin-btn"
                                        onclick="checkIn(<?= (int) $a['id'] ?>, this)">Check In</button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Today's Departures -->
                <div class="form-box">
                    <h3 class="form-box-title">Today's Departures</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($departures)): ?>
                            <tr><td colspan="4" style="text-align:center;color:#888;">No departures today.</td></tr>
                            <?php else: foreach ($departures as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['guest_name']) ?></td>
                                <td><?= htmlspecialchars($d['room_number']) ?></td>
                                <td><?= htmlspecialchars($d['room_type_name']) ?></td>
                                <td>
                                    <button class="action-btn checkout-btn"
                                        onclick="checkOut(<?= (int) $d['id'] ?>, this)">Check Out</button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Revenue Chart -->
            <div class="form-box" style="margin-top:20px;">
                <h3 class="form-box-title">Revenue — Last 8 Weeks</h3>
                <canvas id="revenueChart" height="100"></canvas>
            </div>

        </div><!-- end dashboard -->


        <!-- ═══════════════════════════
             SECTION 2: ROOM TYPES
        ════════════════════════════ -->
        <div id="section-room-types" class="section" style="display:none;">

            <h2>Room Types</h2>
            <p class="subtitle">Manage room categories</p>

            <?php $amenityOptions = ['WiFi','AC','TV','Minibar','Safe','Bathtub','Balcony'];
                  $selectedAmenities = $editingRoomType ? array_map('trim', explode(',', $editingRoomType['amenities'])) : [];
            ?>

            <!-- Add / Edit Room Type Form -->
            <div class="form-box">
                <h3 class="form-box-title"><?= $editingRoomType ? 'Edit Room Type' : 'Add New Room Type' ?></h3>
                <form method="POST" action="../controllers/roomtypeController.php" enctype="multipart/form-data">

                    <?php if ($editingRoomType): ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= (int) $editingRoomType['id'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add">
                    <?php endif; ?>

                    <div class="two-col">
                        <div>
                            <label>Name</label>
                            <select name="name">
                                <option value="">— Select —</option>
                                <?php foreach (['Standard','Deluxe','Suite'] as $n): ?>
                                <option value="<?= $n ?>" <?= ($editingRoomType && $editingRoomType['name'] === $n) ? 'selected' : '' ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Price Per Night (৳)</label>
                            <input type="number" step="0.01" name="price_per_night" placeholder="5000" min="1"
                                value="<?= $editingRoomType ? htmlspecialchars($editingRoomType['price_per_night']) : '' ?>">
                        </div>
                    </div>

                    <label>Max Capacity (guests)</label>
                    <input type="number" name="max_capacity" placeholder="2" min="1" max="10"
                        value="<?= $editingRoomType ? htmlspecialchars($editingRoomType['max_capacity']) : '' ?>">

                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Describe this room type..."><?= $editingRoomType ? htmlspecialchars($editingRoomType['description']) : '' ?></textarea>

                    <label>Thumbnail Image (JPEG/PNG, max 2MB)<?= $editingRoomType ? ' — leave blank to keep current image' : '' ?></label>
                    <input type="file" name="thumbnail" accept="image/jpeg,image/png" class="file-input">

                    <label>Amenities</label>
                    <div class="amenity-checkboxes">
                        <?php foreach ($amenityOptions as $a): ?>
                        <label class="check-label">
                            <input type="checkbox" name="amenities[]" value="<?= $a ?>" <?= in_array($a, $selectedAmenities, true) ? 'checked' : '' ?>> <?= $a ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit"><?= $editingRoomType ? 'Save Changes' : 'Add Room Type' ?></button>
                    <?php if ($editingRoomType): ?>
                        <a href="admin.php#room-types" style="margin-left:10px;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Room Types List -->
            <div class="form-box" style="padding:0; overflow:hidden;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price/Night</th>
                            <th>Capacity</th>
                            <th>Amenities</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($roomTypes)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#888;">No room types yet.</td></tr>
                        <?php else: foreach ($roomTypes as $rt): ?>
                        <tr>
                            <td><?= htmlspecialchars($rt['name']) ?></td>
                            <td>৳ <?= number_format($rt['price_per_night'], 2) ?></td>
                            <td><?= (int) $rt['max_capacity'] ?></td>
                            <td><?= amenityTags($rt['amenities']) ?></td>
                            <td>
                                <a href="admin.php?edit_room_type=<?= (int) $rt['id'] ?>#room-types" class="edit-link">Edit</a>
                                <form method="POST" action="../controllers/roomtypeController.php" style="display:inline;"
                                      onsubmit="return confirm('Delete this room type? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $rt['id'] ?>">
                                    <button type="submit" class="delete-link" style="background:none;border:none;padding:0;cursor:pointer;color:#b71c1c;text-decoration:underline;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

        </div><!-- end room-types -->


        <!-- ═══════════════════════════
             SECTION 3: ROOMS
        ════════════════════════════ -->
        <div id="section-rooms" class="section" style="display:none;">

            <h2>Rooms</h2>
            <p class="subtitle">Manage individual rooms</p>

            <!-- Add / Edit Room Form -->
            <div class="form-box">
                <h3 class="form-box-title"><?= $editingRoom ? 'Edit Room' : 'Add New Room' ?></h3>
                <form method="POST" action="../controllers/roomController.php">

                    <?php if ($editingRoom): ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= (int) $editingRoom['id'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add">
                    <?php endif; ?>

                    <div class="two-col">
                        <div>
                            <label>Room Number</label>
                            <input type="text" name="room_number" placeholder="101"
                                value="<?= $editingRoom ? htmlspecialchars($editingRoom['room_number']) : '' ?>">
                        </div>
                        <div>
                            <label>Floor</label>
                            <input type="number" name="floor" placeholder="1" min="1"
                                value="<?= $editingRoom ? htmlspecialchars($editingRoom['floor']) : '' ?>">
                        </div>
                    </div>

                    <div class="two-col">
                        <div>
                            <label>Room Type</label>
                            <select name="room_type_id">
                                <option value="">— Select type —</option>
                                <?php foreach ($roomTypes as $rt): ?>
                                <option value="<?= (int) $rt['id'] ?>" <?= ($editingRoom && (int) $editingRoom['room_type_id'] === (int) $rt['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rt['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Initial Status</label>
                            <select name="status">
                                <option value="available" <?= (!$editingRoom || $editingRoom['status'] === 'available') ? 'selected' : '' ?>>Available</option>
                                <option value="maintenance" <?= ($editingRoom && $editingRoom['status'] === 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit"><?= $editingRoom ? 'Save Changes' : 'Add Room' ?></button>
                    <?php if ($editingRoom): ?>
                        <a href="admin.php#rooms" style="margin-left:10px;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Rooms List -->
            <div class="form-box" style="padding:0; overflow:hidden;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Room No.</th>
                            <th>Floor</th>
                            <th>Type</th>
                            <th>Occupancy Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rooms)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#888;">No rooms yet.</td></tr>
                        <?php else: foreach ($rooms as $room):
                            if ($room['room_status'] === 'maintenance') {
                                $badgeClass = 'maintenance-badge'; $badgeLabel = 'Maintenance';
                            } elseif ($room['is_occupied']) {
                                $badgeClass = 'booked-badge'; $badgeLabel = 'Booked';
                            } else {
                                $badgeClass = 'available-badge'; $badgeLabel = 'Available';
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($room['room_number']) ?></td>
                            <td><?= (int) $room['floor'] ?></td>
                            <td><?= htmlspecialchars($room['room_type_name']) ?></td>
                            <td>
                                <?php if ($room['is_occupied'] && $room['room_status'] !== 'maintenance'): ?>
                                <span class="status-toggle <?= $badgeClass ?>" style="opacity:.7;cursor:default;"><?= $badgeLabel ?></span>
                                <?php else: ?>
                                <button class="status-toggle <?= $badgeClass ?>"
                                    onclick="toggleStatus(<?= (int) $room['id'] ?>, this)"><?= $badgeLabel ?></button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="admin.php?edit_room=<?= (int) $room['id'] ?>#rooms" class="edit-link">Edit</a>
                                <form method="POST" action="../controllers/roomController.php" style="display:inline;"
                                      onsubmit="return confirm('Delete this room? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $room['id'] ?>">
                                    <button type="submit" class="delete-link" style="background:none;border:none;padding:0;cursor:pointer;color:#b71c1c;text-decoration:underline;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

        </div><!-- end rooms -->


        <!-- ═══════════════════════════
             SECTION 4: BOOKINGS
        ════════════════════════════ -->
        <div id="section-bookings" class="section" style="display:none;">

            <h2>All Bookings</h2>
            <p class="subtitle">View and manage reservations</p>

            <!-- Filter Form -->
            <div class="form-box">
                <h3 class="form-box-title">Filter Bookings</h3>
                <form method="GET" action="admin.php">
                    <input type="hidden" name="section" value="bookings">
                    <div class="three-col">
                        <div>
                            <label>Status</label>
                            <select name="status">
                                <option value="">All</option>
                                <?php foreach (['Booked','Checked-In','Checked-Out','Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>From Date</label>
                            <input type="date" name="date_from" value="<?= htmlspecialchars($filterDateFrom) ?>">
                        </div>
                        <div>
                            <label>To Date</label>
                            <input type="date" name="date_to" value="<?= htmlspecialchars($filterDateTo) ?>">
                        </div>
                    </div>
                    <button type="submit" style="width:auto; padding:8px 24px; margin-top:12px;">Apply Filter</button>
                    <a href="admin.php#bookings" style="margin-left:10px;">Clear</a>
                </form>
            </div>

            <!-- Bookings Table -->
            <div class="form-box" style="padding:0; overflow:hidden;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allBookings)): ?>
                        <tr><td colspan="8" style="text-align:center;color:#888;">No bookings match this filter.</td></tr>
                        <?php else: foreach ($allBookings as $b): ?>
                        <tr>
                            <td><?= (int) $b['id'] ?></td>
                            <td><?= htmlspecialchars($b['guest_name']) ?></td>
                            <td><?= htmlspecialchars($b['room_number']) ?></td>
                            <td><?= htmlspecialchars($b['room_type_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($b['checkin_date'])) ?></td>
                            <td><?= date('M j, Y', strtotime($b['checkout_date'])) ?></td>
                            <td>৳ <?= number_format($b['total_price'], 2) ?></td>
                            <td><span class="badge <?= bookingStatusBadgeClass($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

        </div><!-- end bookings -->

    </div><!-- end main-content -->

</div><!-- end page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

// ── Open the correct section if URL has a hash ──
(function () {
    const params = new URLSearchParams(window.location.search);
    const hash = window.location.hash.replace('#', '') || params.get('section') ||
                 (params.has('edit_room_type') ? 'room-types' : '') ||
                 (params.has('edit_room') ? 'rooms' : '') ||
                 (params.has('status') || params.has('date_from') || params.has('date_to') ? 'bookings' : '');
    const validSections = ['dashboard', 'room-types', 'rooms', 'bookings'];
    if (hash && validSections.includes(hash)) {
        const targetLink = document.querySelector('.nav-item[href="#' + hash + '"]');
        if (targetLink) showSection(hash, targetLink);
    }
})();

// ── Section switcher ──
function showSection(name, clickedLink) {
    document.querySelectorAll('.section').forEach(function(s) {
        s.style.display = 'none';
    });
    document.querySelectorAll('.nav-item').forEach(function(n) {
        n.classList.remove('active');
    });
    document.getElementById('section-' + name).style.display = 'block';
    clickedLink.classList.add('active');
    return false;
}

// ── Revenue Chart ──
var ctx = document.getElementById('revenueChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($revenueChart['labels']) ?>,
            datasets: [{
                label: 'Revenue (৳)',
                data: <?= json_encode($revenueChart['data']) ?>,
                backgroundColor: '#1a3a6b',
                borderRadius: 6
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

// ── AJAX: Check In ──
function checkIn(bookingId, btn) {
    fetch('../ajax/checkin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: bookingId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            btn.textContent = 'Check Out';
            btn.className = 'action-btn checkout-btn';
            btn.setAttribute('onclick', 'checkOut(' + bookingId + ', this)');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Something went wrong.'));
}

// ── AJAX: Check Out ──
function checkOut(bookingId, btn) {
    fetch('../ajax/checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: bookingId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            btn.textContent = 'Done';
            btn.disabled = true;
            btn.style.opacity = '0.5';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Something went wrong.'));
}

// ── AJAX: Toggle room maintenance status ──
function toggleStatus(roomId, btn) {
    fetch('../ajax/toggleStatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ room_id: roomId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.new_status === 'available') {
                btn.textContent = 'Available';
                btn.className = 'status-toggle available-badge';
            } else {
                btn.textContent = 'Maintenance';
                btn.className = 'status-toggle maintenance-badge';
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Something went wrong.'));
}

</script>

</body>
</html>