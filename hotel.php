<?php

session_start();

require_once __DIR__ . '/config/database.php';


// =========================================
// AMBIL HOTEL ID
// =========================================

$hotelId = filter_input(
    INPUT_GET,
    'hotel_id',
    FILTER_VALIDATE_INT
);

$checkin = $_GET['checkin'] ?? date('Y-m-d');

$checkout = $_GET['checkout']
    ?? date('Y-m-d', strtotime('+1 day'));

$guests = filter_input(
    INPUT_GET,
    'guests',
    FILTER_VALIDATE_INT
) ?: 2;

if (!$hotelId) {
    header('Location: index.php');
    exit;
}


// =========================================
// AMBIL DATA HOTEL
// =========================================

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        location,
        description,
        address,
        phone,
        rating,
        image
    FROM hotels
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$hotelId]);

$hotel = $stmt->fetch(PDO::FETCH_ASSOC);


// =========================================
// HOTEL TIDAK DITEMUKAN
// =========================================

if (!$hotel) {
    header('Location: index.php');
    exit;
}

// =========================================
// AMBIL RINGKASAN REVIEW
// =========================================

$stmt = $pdo->prepare("
    SELECT
        AVG(rating) AS average_rating,
        COUNT(*) AS total_reviews
    FROM hotel_reviews
    WHERE hotel_id = ?
");

$stmt->execute([$hotelId]);

$reviewSummary = $stmt->fetch(PDO::FETCH_ASSOC);

$averageRating = $reviewSummary['average_rating']
    ?? $hotel['rating'];

$totalReviews = (int) (
    $reviewSummary['total_reviews'] ?? 0
);

// =========================================
// AMBIL DETAIL REVIEW
// =========================================

$stmt = $pdo->prepare("
    SELECT
        hr.rating,
        hr.comment,
        hr.created_at,
        u.name AS user_name

    FROM hotel_reviews hr

    INNER JOIN users u
        ON u.id = hr.user_id

    WHERE hr.hotel_id = ?

    ORDER BY hr.created_at DESC
");

$stmt->execute([$hotelId]);

$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================
// AMBIL TIPE KAMAR
// =========================================

$stmt = $pdo->prepare("
    SELECT
        id,
        hotel_id,
        name,
        description,
        capacity,
        price,
        image
    FROM room_types
    WHERE hotel_id = ?
    ORDER BY price ASC
");

$stmt->execute([$hotelId]);

$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// =========================================
// AMBIL SEMUA KAMAR
// =========================================

$stmt = $pdo->prepare("
    SELECT
        rm.id,
        rm.room_number,
        rm.status,
        rm.room_type_id,
        rt.name AS room_type_name
    FROM rooms rm

    INNER JOIN room_types rt
        ON rt.id = rm.room_type_id

    WHERE rm.hotel_id = ?

    ORDER BY
        rt.price ASC,
        rm.room_number ASC
");

$stmt->execute([$hotelId]);

$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);


// =========================================
// FORMAT RUPIAH
// =========================================

function rupiah($number)
{
    return number_format(
        $number,
        0,
        ',',
        '.'
    );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($hotel['name']) ?> — Stayora
    </title>

    <link
        rel="stylesheet"
        href="public/css/style.css"
    >

</head>


<body>

<div class="app">


    <!-- =========================================
         NAVBAR
    ========================================== -->

    <header class="nav">

        <div class="brand">
            stay<span>ora</span>
        </div>


        <nav class="navlinks">

            <a href="index.php">
                Hotel
            </a>

            <a href="index.php#promo">
                Promo
            </a>

            <a href="index.php#pesanan">
                Pesanan
            </a>

            <a href="index.php#bantuan">
                Bantuan
            </a>

        </nav>


        <?php if (isset($_SESSION['user_id'])): ?>

            <span class="profile">

                Halo,
                <?= htmlspecialchars(
                    $_SESSION['user_name']
                ) ?>

            </span>


            <a
                class="profile"
                href="auth/logout.php"
            >
                Logout
            </a>

        <?php else: ?>

            <a
                class="profile"
                href="auth/login.php"
            >
                Masuk
            </a>

        <?php endif; ?>

    </header>


    <!-- =========================================
         HOTEL DETAIL
    ========================================== -->

    <main class="hotel-detail-page">


        <!-- =====================================
             BACK
        ====================================== -->

        <a
            href="index.php"
            class="back-home"
        >
            ← Kembali ke daftar hotel
        </a>


        <!-- =====================================
             HERO HOTEL
        ====================================== -->

        <section class="hotel-detail-card">


            <?php if (!empty($hotel['image'])): ?>

                <div class="hotel-detail-image">

                    <img
                        src="uploads/hotels/<?= htmlspecialchars($hotel['image']) ?>"
                        alt="<?= htmlspecialchars($hotel['name']) ?>"
                    >

                </div>

            <?php endif; ?>


            <div class="hotel-detail-content">


                <div class="hotel-detail-rating">

                <?= number_format(
                    (float) $averageRating,
                    1
                ) ?>

                ★

                <span>
                    (<?= $totalReviews ?> ulasan)
                </span>

            </div>


                <h1>

                    <?= htmlspecialchars(
                        $hotel['name']
                    ) ?>

                </h1>


                <p class="hotel-location">

                    📍

                    <?= htmlspecialchars(
                        $hotel['location']
                    ) ?>

                </p>


                <?php if (!empty($hotel['address'])): ?>

                    <p>

                        📌

                        <?= htmlspecialchars(
                            $hotel['address']
                        ) ?>

                    </p>

                <?php endif; ?>


                <?php if (!empty($hotel['phone'])): ?>

                    <p>

                        ☎

                        <?= htmlspecialchars(
                            $hotel['phone']
                        ) ?>

                    </p>

                <?php endif; ?>


            </div>

        </section>


        <!-- =====================================
             DESKRIPSI
        ====================================== -->

        <section class="hotel-section">


            <h2>
                Tentang Hotel
            </h2>


            <?php if (!empty($hotel['description'])): ?>

                <p class="hotel-description-full">

                    <?= nl2br(
                        htmlspecialchars(
                            $hotel['description']
                        )
                    ) ?>

                </p>

            <?php else: ?>

                <p>
                    Belum ada deskripsi hotel.
                </p>

            <?php endif; ?>


        </section>


        <!-- =====================================
             TIPE KAMAR
        ====================================== -->

        <section class="hotel-section">


            <div class="sectionhead">

                <div>

                    <h2>
                        Tipe Kamar
                    </h2>

                    <p>
                        Pilih tipe kamar yang sesuai dengan kebutuhan Anda.
                    </p>

                </div>

            </div>


            <?php if (!$roomTypes): ?>

                <div class="empty">

                    <h3>
                        Belum ada tipe kamar
                    </h3>

                    <p>
                        Hotel ini belum memiliki tipe kamar.
                    </p>

                </div>

            <?php else: ?>


                <div class="room-type-list">


                    <?php foreach ($roomTypes as $roomType): ?>

                        <article class="room-type-card">


                            <?php if (!empty($roomType['image'])): ?>

                                <div class="room-type-image">

                                    <img
                                        src="uploads/room-types/<?= htmlspecialchars($roomType['image']) ?>"
                                        alt="<?= htmlspecialchars($roomType['name']) ?>"
                                    >

                                </div>

                            <?php endif; ?>


                            <div class="room-type-content">


                                <h3>

                                    <?= htmlspecialchars(
                                        $roomType['name']
                                    ) ?>

                                </h3>


                                <?php if (!empty($roomType['description'])): ?>

                                    <p>

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $roomType['description']
                                            )
                                        ) ?>

                                    </p>

                                <?php endif; ?>


                                <div class="room-type-info">

                                    <span>

                                        👤

                                        Maksimal

                                        <?= (int)$roomType['capacity'] ?>

                                        tamu

                                    </span>


                                    <strong>

                                        Rp

                                        <?= rupiah(
                                            $roomType['price']
                                        ) ?>

                                        <small>
                                            / malam
                                        </small>

                                    </strong>

                                </div>


                            </div>


                            


                        </article>

                    <?php endforeach; ?>


                </div>

            <?php endif; ?>


        </section>

        <!-- =====================================
            ULASAN TAMU
        ====================================== -->

        <section class="hotel-section">

            <div class="sectionhead">

                <div>

                    <h2>
                        Ulasan Tamu
                    </h2>

                    <p>
                        Pengalaman pengguna yang pernah menginap di hotel ini.
                    </p>

                </div>

                <div class="review-summary">

                    <strong>
                        <?= number_format(
                            (float) $averageRating,
                            1
                        ) ?>
                        ★
                    </strong>

                    <span>
                        <?= $totalReviews ?>
                        ulasan
                    </span>

                </div>

            </div>


            <?php if (!$reviews): ?>

                <div class="empty">

                    <h3>
                        Belum ada ulasan
                    </h3>

                    <p>
                        Belum ada pengguna yang memberikan ulasan
                        untuk hotel ini.
                    </p>

                </div>

            <?php else: ?>

                <div class="review-list">

                    <?php foreach ($reviews as $review): ?>

                        <article class="review-item">

                            <div class="review-header">

                                <div>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $review['user_name']
                                        ) ?>
                                    </strong>

                                    <small>

                                        <?= date(
                                            'd M Y',
                                            strtotime(
                                                $review['created_at']
                                            )
                                        ) ?>

                                    </small>

                                </div>


                                <span class="review-rating">

                                    <?= number_format(
                                        (float) $review['rating'],
                                        1
                                    ) ?>

                                    ★

                                </span>

                            </div>


                            <?php if (!empty($review['comment'])): ?>

                                <p class="review-comment-text">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $review['comment']
                                        )
                                    ) ?>

                                </p>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


        <!-- =====================================
             DAFTAR KAMAR
        ====================================== -->

        <section class="hotel-section">


            <h2>
                Daftar Kamar
            </h2>


            <p>
                Informasi kamar yang tersedia di hotel ini.
            </p>


            <?php if (!$rooms): ?>

                <div class="empty">

                    <p>
                        Belum ada kamar yang terdaftar.
                    </p>

                </div>

            <?php else: ?>


                <div class="room-list">


                    <?php foreach ($rooms as $room): ?>

                        <div class="room-item">


                            <div>

                                <strong>

                                    Kamar

                                    <?= htmlspecialchars(
                                        $room['room_number']
                                    ) ?>

                                </strong>


                                <small>

                                    <?= htmlspecialchars(
                                        $room['room_type_name']
                                    ) ?>

                                </small>

                            </div>


                            <?php if (
                                $room['status'] === 'available'
                            ): ?>

                                <span class="room-status available">

                                    Tersedia

                                </span>

                            <?php else: ?>

                                <span class="room-status">

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $room['status']
                                        )
                                    ) ?>

                                </span>

                            <?php endif; ?>


                        </div>

                    <?php endforeach; ?>


                </div>

            <?php endif; ?>


        </section>


        <!-- =====================================
             CTA BOOKING
        ====================================== -->

        <?php if ($roomTypes): ?>

            <section class="hotel-booking-cta">


                <h2>
                    Siap melakukan reservasi?
                </h2>


                <p>
                    Pilih tipe kamar dan lanjutkan ke proses reservasi.
                </p>


                <?php if (isset($_SESSION['user_id'])): ?>

                    <a
                        class="confirm"
                        href="booking.php?hotel_id=<?= $hotel['id'] ?>"
                    >
                        Lanjut ke Reservasi
                    </a>

                <?php else: ?>

                    <a
                        class="confirm"
                        href="auth/login.php"
                    >
                        Login untuk Reservasi
                    </a>

                <?php endif; ?>


            </section>

        <?php endif; ?>


    </main>

</div>

</body>

</html>