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
// AMBIL RESERVATION ID
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
// AMBIL DATA RESERVASI
// =========================================

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.user_id,
        r.check_in,
        r.check_out,
        r.guests,
        r.total_price,
        r.status,

        h.name AS hotel_name,
        h.location,

        rt.name AS room_type,
        rm.room_number

    FROM reservations r

    INNER JOIN rooms rm
        ON r.room_id = rm.id

    INNER JOIN room_types rt
        ON rm.room_type_id = rt.id

    INNER JOIN hotels h
        ON rm.hotel_id = h.id

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

// =========================================
// CEK STATUS
// =========================================

if ($reservation['status'] !== 'pending') {
    header(
        'Location: reservation-success.php?reservation_id=' .
        $reservationId
    );
    exit;
}

$error = '';

// =========================================
// PROSES PEMBAYARAN
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $method = $_POST['method'] ?? '';

    $allowedMethods = [
        'bank_transfer',
        'qris',
        'cash'
    ];

    if (!in_array($method, $allowedMethods, true)) {

        $error = 'Metode pembayaran tidak valid.';

    } else {

        try {

            $pdo->beginTransaction();

            // =====================================
            // UPDATE PAYMENT
            // =====================================

            $stmt = $pdo->prepare("
                UPDATE payments

                SET
                    method = ?,
                    status = 'paid',
                    paid_at = NOW()

                WHERE reservation_id = ?
            ");

            $stmt->execute([
                $method,
                $reservationId
            ]);

            // =====================================
            // UPDATE RESERVATION
            // =====================================

            $stmt = $pdo->prepare("
                UPDATE reservations

                SET status = 'confirmed'

                WHERE id = ?
                  AND user_id = ?
                  AND status = 'pending'
            ");

            $stmt->execute([
                $reservationId,
                $_SESSION['user_id']
            ]);

            $pdo->commit();

            // =====================================
            // REDIRECT
            // =====================================

            header(
                'Location: reservation-success.php?reservation_id=' .
                $reservationId
            );

            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'Pembayaran gagal diproses. Silakan coba lagi.';
        }
    }
}

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

    <title>Pembayaran — Stayora</title>

    <link
        rel="stylesheet"
        href="public/css/style.css"
    >

</head>

<body>

<div class="app">

    <!-- NAVBAR -->

    <header class="nav">

        <div class="brand">
            stay<span>ora</span>
        </div>

        <a
            class="back"
            href="booking.php?hotel_id=<?= $reservation['hotel_name'] ?>"
        >
            ← Kembali
        </a>

    </header>


    <!-- CONTENT -->

    <main class="booking-page">

        <div class="booking-card">

            <!-- =====================================
                 SUMMARY
            ====================================== -->

            <div class="booking-summary">

                <span class="badge">
                    Reservasi #<?= $reservation['id'] ?>
                </span>

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
                    <?= htmlspecialchars(
                        $reservation['check_in']
                    ) ?>

                    sampai

                    <?= htmlspecialchars(
                        $reservation['check_out']
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

            </div>


            <!-- =====================================
                 ERROR
            ====================================== -->

            <?php if ($error): ?>

                <div
                    class="status status-cancelled"
                    style="margin-bottom:20px;"
                >
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <!-- =====================================
                 PAYMENT FORM
            ====================================== -->

            <form
                method="POST"
                class="reservation-form"
            >

                <h2>
                    Metode Pembayaran
                </h2>

                <label>
                    Pilih metode pembayaran
                </label>

                <select
                    name="method"
                    required
                >

                    <option value="">
                        -- Pilih metode pembayaran --
                    </option>

                    <option value="bank_transfer">
                        Bank Transfer
                    </option>

                    <option value="qris">
                        QRIS
                    </option>

                    <option value="cash">
                        Bayar di Hotel
                    </option>

                </select>


                <button
                    type="submit"
                    class="confirm"
                >
                    Konfirmasi Pembayaran
                </button>

            </form>

        </div>

    </main>

</div>

</body>

</html>