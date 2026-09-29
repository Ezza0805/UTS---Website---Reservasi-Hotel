<?php

session_start();

require_once __DIR__ . '/config/database.php';

// =========================================
// CEK LOGIN
// =========================================

if (!isset($_SESSION['user_id'])) {

    header('Location: auth/login.php');
    exit;

}

$userId = (int) $_SESSION['user_id'];

// =========================================
// AMBIL RESERVASI USER
// =========================================

$stmt = $pdo->prepare("
    SELECT

        r.id,
        r.check_in,
        r.check_out,
        r.guests,
        r.total_price,
        r.status AS reservation_status,
        r.created_at,

        rm.room_number,

        rt.name AS room_type_name,

        h.id AS hotel_id,
        h.name AS hotel_name,
        h.location AS hotel_location,
        h.image AS hotel_image,

        p.method AS payment_method,
        p.status AS payment_status

    FROM reservations r

    INNER JOIN rooms rm
        ON rm.id = r.room_id

    INNER JOIN room_types rt
        ON rt.id = rm.room_type_id

    INNER JOIN hotels h
        ON h.id = rm.hotel_id

    LEFT JOIN payments p
        ON p.reservation_id = r.id

    WHERE r.user_id = ?

    ORDER BY r.created_at DESC
");

$stmt->execute([$userId]);

$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
        Pesanan Saya — Stayora
    </title>

    <link
        rel="stylesheet"
        href="public/css/style.css"
    >

</head>

<body>

<div class="app">

    <!-- =====================================
         NAVBAR
    ====================================== -->

    <header class="nav">

        <div class="brand">
            stay<span>ora</span>
        </div>


        <nav class="navlinks">

            <a href="index.php">
                Hotel
            </a>

            <a href="pesanan.php">
                Pesanan
            </a>

        </nav>


        <div>

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

        </div>

    </header>


    <!-- =====================================
         PESANAN
    ====================================== -->

    <main class="orders-page">

        <div class="orders-header">

            <h1>
                Pesanan Saya
            </h1>

            <p>
                Riwayat reservasi dan status pembayaran Anda.
            </p>

        </div>


        <?php if (!$reservations): ?>

            <div class="empty">

                <h3>
                    Belum ada reservasi
                </h3>

                <p>
                    Anda belum memiliki riwayat reservasi.
                </p>

                <a
                    href="index.php"
                    class="confirm"
                >
                    Cari Hotel
                </a>

            </div>

        <?php else: ?>


            <div class="order-list">

                <?php foreach ($reservations as $reservation): ?>

                    <?php

                    $paymentStatus =
                        $reservation['payment_status']
                        ?? 'pending';

                    $reservationStatus =
                        $reservation['reservation_status'];

                    ?>


                    <article class="order-card">

                        <?php if (!empty(
                            $reservation['hotel_image']
                        )): ?>

                            <img
                                class="order-image"
                                src="uploads/hotels/<?= htmlspecialchars(
                                    $reservation['hotel_image']
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $reservation['hotel_name']
                                ) ?>"
                            >

                        <?php endif; ?>


                        <div class="order-content">


                            <!-- HOTEL -->

                            <div class="order-top">

                                <div>

                                    <h2>

                                        <?= htmlspecialchars(
                                            $reservation['hotel_name']
                                        ) ?>

                                    </h2>

                                    <p>

                                        📍
                                        <?= htmlspecialchars(
                                            $reservation[
                                                'hotel_location'
                                            ]
                                        ) ?>

                                    </p>

                                </div>


                                <!-- STATUS PEMBAYARAN -->

                                <?php if (
                                    $paymentStatus === 'paid'
                                ): ?>

                                    <span class="order-status status-paid">
                                        ✓ Sudah Dibayar
                                    </span>

                                <?php else: ?>

                                    <span class="order-status status-pending">
                                        ⏳ Belum Dibayar
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- DETAIL -->

                            <div class="order-detail">


                                <div>

                                    <small>
                                        Tipe Kamar
                                    </small>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $reservation[
                                                'room_type_name'
                                            ]
                                        ) ?>

                                    </strong>

                                </div>


                                <div>

                                    <small>
                                        Nomor Kamar
                                    </small>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $reservation[
                                                'room_number'
                                            ]
                                        ) ?>

                                    </strong>

                                </div>


                                <div>

                                    <small>
                                        Check-in
                                    </small>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $reservation[
                                                'check_in'
                                            ]
                                        ) ?>

                                    </strong>

                                </div>


                                <div>

                                    <small>
                                        Check-out
                                    </small>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $reservation[
                                                'check_out'
                                            ]
                                        ) ?>

                                    </strong>

                                </div>


                                <div>

                                    <small>
                                        Tamu
                                    </small>

                                    <strong>

                                        <?= (int)
                                            $reservation['guests'] ?>

                                        orang

                                    </strong>

                                </div>


                                <div>

                                    <small>
                                        Total
                                    </small>

                                    <strong>

                                        Rp
                                        <?= rupiah(
                                            $reservation[
                                                'total_price'
                                            ]
                                        ) ?>

                                    </strong>

                                </div>

                            </div>


                            <!-- FOOTER -->

                            <div class="order-footer">


                                <div>

                                    Status reservasi:

                                    <?php

                                    $statusLabels = [

                                        'pending'
                                            => 'Menunggu',

                                        'confirmed'
                                            => 'Dikonfirmasi',

                                        'completed'
                                            => 'Selesai',

                                        'cancelled'
                                            => 'Dibatalkan'

                                    ];

                                    echo htmlspecialchars(
                                        $statusLabels[
                                            $reservationStatus
                                        ]
                                        ??
                                        ucfirst(
                                            $reservationStatus
                                        )
                                    );

                                    ?>

                                </div>


                                <div class="order-actions">


                                    <!-- BELUM BAYAR -->

                                    <?php if (
                                        $paymentStatus !== 'paid'
                                        &&
                                        $reservationStatus
                                            !== 'cancelled'
                                    ): ?>

                                        <a
                                            class="book"
                                            href="payment.php?reservation_id=<?= $reservation['id'] ?>"
                                        >
                                            Lanjut Bayar
                                        </a>

                                    <?php endif; ?>


                                    <!-- RATING -->

                                    <?php if (
                                        $reservationStatus
                                            === 'completed'
                                    ): ?>

                                        <a
                                            class="confirm"
                                            href="review.php?reservation_id=<?= $reservation['id'] ?>"
                                        >
                                            ⭐ Berikan Rating
                                        </a>

                                    <?php endif; ?>


                                </div>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

</div>

</body>

</html>