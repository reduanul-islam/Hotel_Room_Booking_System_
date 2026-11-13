<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include "../../controllers/searchController.php";
?>

<!DOCTYPE html>
<html>

<head>
    <title>Search Rooms</title>
    <link rel="stylesheet" href="../../assets/search_room.css">
</head>

<body>

<div class="page-wrapper">

    <div class="sidebar">

        <div class="panel-brand">HOTEL SYSTEM</div>

        <div class="panel-heading">
            Search <br> Rooms
        </div>

        <div class="sidebar-nav">
            <a href="../user.php" class="nav-item">My Profile</a>
            <a href="mybooking.php" class="nav-item">My Bookings</a>
            <a href="search_room.php" class="nav-item active">Search Rooms</a>
            <a href="../../controllers/logoutController.php" class="nav-item logout">Logout</a>
        </div>

    </div>

    <div class="main-content">

        <h1>Search The Available Rooms</h1>

        <p>Find your perfect and comfortable Room</p>

        <div class="form-box search-form-box">

            <div class="form-box-title">Room Search</div>

            <form method="POST">

                <div class="search-row">

                    <div class="search-field">
                        <label for="checkIn">Check-in Date</label>
                        <input type="date" id="checkIn" name="checkIn" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_SESSION['search_checkin'] ?? '') ?>">
                    </div>

                    <div class="search-field">
                        <label for="checkOut">Check-out Date</label>
                        <input type="date" id="checkOut" name="checkOut" required value="<?= htmlspecialchars($_SESSION['search_checkout'] ?? '') ?>">
                    </div>

                    <div class="search-field">

                        <label for="guests">Number of Guests</label>

                        <select id="guests" name="guests" required>
                            <option value="">Select</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>

                    </div>

                    <div class="search-field search-btn-field">
                        <input type="submit" value="Search Rooms">
                    </div>

                </div>

            </form>

        </div>



        <div class="results-label">Available Rooms</div>
            <div class="room-cards">

            <?php
            if(isset($_SESSION["rooms"]) && count($_SESSION["rooms"]) > 0){

                foreach($_SESSION["rooms"] as $room){
            ?>



            <div class="room-card">

                <?php if (!empty($room['thumbnail_path'])): ?>
                <div class="room-thumb">
                    <img src="../../<?= htmlspecialchars($room['thumbnail_path']) ?>" alt="<?= htmlspecialchars($room['name']) ?>" style="width:100%;height:180px;object-fit:cover;border-radius:8px;margin-bottom:12px;">
                </div>
                <?php endif; ?>

                <div class="room-card-top">

                    <div class="room-type-name"><?= htmlspecialchars($room["name"]); ?></div>

                    <div class="room-price">
                        Tk <?= htmlspecialchars(number_format($room["price_per_night"], 2)); ?>
                        <span>/ night</span>
                    </div>

                </div>

                <div class="room-desc"><?= htmlspecialchars($room["description"]); ?></div>
                <div class="amenity-tags">

                    <?php
                    $amenities = explode(",", $room["amenities"]);

                    foreach($amenities as $amenity){
                    ?>

                    <div class="tag"><?= htmlspecialchars(trim($amenity)); ?></div>

                    <?php
                    }
                    ?>

                </div>

                <div class="room-card-footer">

                    <div class="total-price">
                        Total starts from Tk <?= htmlspecialchars(number_format($room["price_per_night"], 2)); ?>
                    </div>

                    <a href="book.php?room_type_id=<?= urlencode($room['id']) ?>" class="book-btn">Book Now</a>

                </div>

            </div>

            <?php
                }
            }
            else{
            ?>

            <h3>No Rooms Available</h3>

            <?php
            }
            ?>
        </div>
        </form>
        
        

    </div>

</div>

</body>

</html>