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

// =========================================
// RESERVATION ID
// =========================================

$reservationId = filter_input(
    INPUT_GET,
    'reservation_id',
    FILTER_VALIDATE_INT
);

if (!$reservationId) {
    header('Location: index.php');
    exit;
}

// =========================================
// AMBIL RESERVASI
// =========================================

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.check_in,
        r.check_out,
        r.guests,
        r.total_price,
        r.status,

        h.name AS hotel_name,
        h.location,

        rt.name AS room_type,
        rm.room_number,

        p.method,
        p.status AS payment_status,
        p.paid_at

    FROM reservations r

    INNER JOIN rooms rm
        ON r.room_id = rm.id

    INNER JOIN room_types rt
        ON rm.room_type_id = rt.id

    INNER JOIN hotels h
        ON rm.hotel_id = h.id

    LEFT JOIN payments p
        ON p.reservation_id = r.id

    WHERE r.id = ?
      AND r.user_id = ?
");

$stmt->execute([
    $reservationId,
    $_SESSION['user_id']
]);

$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    header('Location: index.php');
    exit;
}

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

    <title>Reservasi Berhasil — Stayora</title>

    <link
        rel="stylesheet"
        href="public/css/style.css"
    >

</head>

<body>

<div class="app">

    <header class="nav">

        <div class="brand">
            stay<span>ora</span>
        </div>

    </header>


    <main class="booking-page">

        <div class="booking-card">

            <div class="success">

                <h2>
                    Reservasi berhasil!
                </h2>

                <p>
                    Pembayaran dan reservasi Anda telah dikonfirmasi.
                </p>

                <p>
                    Nomor reservasi:
                    <strong>
                        #<?= htmlspecialchars(
                            $reservation['id']
                        ) ?>
                    </strong>
                </p>

            </div>


            <div class="booking-summary">

                <h1>
                    <?= htmlspecialchars(
                        $reservation['hotel_name']
                    ) ?>
                </h1>

                <p>
                    📍
                    <?= htmlspecialchars(
                        $reservation['location']
                    ) ?>
                </p>

                <p>
                    <?= htmlspecialchars(
                        $reservation['room_type']
                    ) ?>

                    · Kamar
                    <?= htmlspecialchars(
                        $reservation['room_number']
                    ) ?>
                </p>

                <p>
                    Check-in:
                    <?= htmlspecialchars(
                        $reservation['check_in']
                    ) ?>
                </p>

                <p>
                    Check-out:
                    <?= htmlspecialchars(
                        $reservation['check_out']
                    ) ?>
                </p>

                <p>
                    Jumlah tamu:
                    <?= htmlspecialchars(
                        $reservation['guests']
                    ) ?>
                </p>

                <div class="booking-price">

                    <strong>
                        Rp <?= rupiah(
                            $reservation['total_price']
                        ) ?>
                    </strong>

                    <span>
                        total
                    </span>

                </div>

                <p>
                    Metode pembayaran:
                    <strong>
                        <?= htmlspecialchars(
                            $reservation['method']
                        ) ?>
                    </strong>
                </p>

                <p>
                    Status pembayaran:
                    <strong>
                        Lunas
                    </strong>
                </p>

            </div>


            <a
                class="confirm"
                href="index.php"
            >
                Kembali ke halaman hotel
            </a>

        </div>

    </main>

</div>

</body>

</html>