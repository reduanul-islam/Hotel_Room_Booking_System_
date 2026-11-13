<?php
// Reuses bookController.php's own login-check + POST handling.
// On POST, bookController.php always redirects (success or failure) and exits,
// so the code below only ever runs for a GET request by a logged-in user,
// and $conn / model functions are already available from the include.
include "../../controllers/bookController.php";

$room_type_id = $_GET['room_type_id'] ?? '';
$roomType = $room_type_id !== '' ? get_room_type_by_id($conn, $room_type_id) : null;

$error = $_SESSION['booking_error'] ?? '';
unset($_SESSION['booking_error']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room — Hotel Booking</title>
    <link rel="stylesheet" href="../../assets/book.css">
</head>

<body>

    <h1 style="text-align:center; margin-bottom:24px;">Book Your Stay</h1>

    <?php if ($error): ?>
        <p style="max-width:460px;margin:0 auto 16px;color:#842029;background:#f8d7da;padding:10px 16px;border-radius:8px;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <?php if (!$roomType): ?>
        <p style="text-align:center;">
            No room selected. <a href="search_room.php">Go back to search</a>.
        </p>
    <?php else: ?>

        <div class="container">

            <form method="POST" action="../../controllers/bookController.php">
                <h1>Reservation Details</h1>

                <input type="hidden" name="room_type_id" value="<?= htmlspecialchars($roomType['id']) ?>">

                <fieldset>
                    <label for="checkIn">Check-in Date</label>
                    <input type="date" id="checkIn" name="checkIn" required>
                </fieldset>

                <fieldset>
                    <label for="checkOut">Check-out Date</label>
                    <input type="date" id="checkOut" name="checkOut" required>
                </fieldset>

                <input type="submit" value="Confirm Booking">
            </form>

            <div class="form">
                <h1>Room Summary</h1>
                <?php if (!empty($roomType['thumbnail_path'])): ?>
                <img src="../../<?= htmlspecialchars($roomType['thumbnail_path']) ?>" alt="<?= htmlspecialchars($roomType['name']) ?>" style="width:100%;max-height:200px;object-fit:cover;border-radius:8px;margin-bottom:16px;">
                <?php endif; ?>
                <table>
                    <tr>
                        <td>Room Type</td>
                        <td><label><?= htmlspecialchars($roomType['name']) ?></label></td>
                    </tr>
                    <tr>
                        <td>Description</td>
                        <td><label><?= htmlspecialchars($roomType['description']) ?></label></td>
                    </tr>
                    <tr>
                        <td>Max Guests</td>
                        <td><label><?= htmlspecialchars($roomType['max_capacity']) ?></label></td>
                    </tr>
                    <tr>
                        <td>Price / night</td>
                        <td><label>Tk <?= htmlspecialchars(number_format($roomType['price_per_night'], 2)) ?></label></td>
                    </tr>
                    <tr>
                        <td>Amenities</td>
                        <td><label><?= htmlspecialchars($roomType['amenities']) ?></label></td>
                    </tr>
                </table>
            </div>

        </div>

    <?php endif; ?>

</body>

</html>
