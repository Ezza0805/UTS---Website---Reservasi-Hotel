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
// AMBIL RESERVATION ID
// =========================================

$reservationId = filter_input(
    INPUT_GET,
    'reservation_id',
    FILTER_VALIDATE_INT
);

if (!$reservationId) {
    header('Location: pesanan.php');
    exit;
}

// =========================================
// AMBIL DATA RESERVASI
// =========================================

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.user_id,
        r.status,
        rm.room_type_id,
        rt.name AS room_type_name,
        h.id AS hotel_id,
        h.name AS hotel_name,
        h.image AS hotel_image

    FROM reservations r

    INNER JOIN rooms rm
        ON rm.id = r.room_id

    INNER JOIN room_types rt
        ON rt.id = rm.room_type_id

    INNER JOIN hotels h
        ON h.id = rm.hotel_id

    WHERE r.id = ?
      AND r.user_id = ?

    LIMIT 1
");

$stmt->execute([
    $reservationId,
    $userId
]);

$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

// =========================================
// CEK RESERVASI
// =========================================

if (!$reservation) {
    header('Location: pesanan.php');
    exit;
}

// =========================================
// HANYA RESERVASI COMPLETED
// =========================================

if ($reservation['status'] !== 'completed') {
    header('Location: pesanan.php');
    exit;
}

// =========================================
// CEK APAKAH SUDAH MEMBERIKAN REVIEW
// =========================================

$stmt = $pdo->prepare("
    SELECT
        id,
        rating,
        comment
    FROM hotel_reviews
    WHERE hotel_id = ?
      AND user_id = ?
    LIMIT 1
");

$stmt->execute([
    $reservation['hotel_id'],
    $userId
]);

$existingReview = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingReview) {
    header(
        'Location: hotel.php?hotel_id=' .
        $reservation['hotel_id']
    );
    exit;
}

// =========================================
// PROSES FORM
// =========================================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rating = filter_input(
        INPUT_POST,
        'rating',
        FILTER_VALIDATE_FLOAT
    );

    $comment = trim(
        $_POST['comment'] ?? ''
    );

    // =====================================
    // VALIDASI RATING
    // =====================================

    if (
        $rating === false ||
        $rating === null ||
        $rating < 1 ||
        $rating > 5
    ) {

        $error =
            'Rating harus antara 1 sampai 5.';

    } else {

        // =================================
        // CEK LAGI UNTUK MENCEGAH DUPLIKAT
        // =================================

        $stmt = $pdo->prepare("
            SELECT id
            FROM hotel_reviews
            WHERE hotel_id = ?
              AND user_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $reservation['hotel_id'],
            $userId
        ]);

        if ($stmt->fetch()) {

            $error =
                'Anda sudah memberikan ulasan untuk hotel ini.';

        } else {

            // =============================
            // INSERT REVIEW
            // =============================

            $stmt = $pdo->prepare("
                INSERT INTO hotel_reviews (
                    hotel_id,
                    user_id,
                    rating,
                    comment
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $reservation['hotel_id'],
                $userId,
                $rating,
                $comment !== ''
                    ? $comment
                    : null
            ]);

            // =============================
            // KEMBALI KE HOTEL
            // =============================

            header(
                'Location: hotel.php?hotel_id=' .
                $reservation['hotel_id']
            );

            exit;
        }
    }
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
        Berikan Rating — Stayora
    </title>

    <link
        rel="stylesheet"
        href="public/css/style.css"
    >

</head>

<body>

<div class="app">

    <main class="review-page">

        <a
            href="pesanan.php"
            class="back-home"
        >
            ← Kembali ke Pesanan
        </a>


        <div class="review-card">

            <?php if (!empty($reservation['hotel_image'])): ?>

                <img
                    class="review-hotel-image"
                    src="uploads/hotels/<?= htmlspecialchars(
                        $reservation['hotel_image']
                    ) ?>"
                    alt="<?= htmlspecialchars(
                        $reservation['hotel_name']
                    ) ?>"
                >

            <?php endif; ?>


            <h1>
                Bagaimana pengalaman Anda?
            </h1>

            <p>

                Berikan rating untuk

                <strong>
                    <?= htmlspecialchars(
                        $reservation['hotel_name']
                    ) ?>
                </strong>

            </p>

            <p class="review-room">

                Tipe kamar:
                <?= htmlspecialchars(
                    $reservation['room_type_name']
                ) ?>

            </p>


            <?php if ($error): ?>

                <div class="error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST">

                <div class="rating-input">

                    <label>
                        Rating
                    </label>

                    <div class="stars">

                        <?php for ($i = 5; $i >= 1; $i--): ?>

                            <input
                                type="radio"
                                id="star<?= $i ?>"
                                name="rating"
                                value="<?= $i ?>"
                                required
                            >

                            <label for="star<?= $i ?>">
                                ★
                            </label>

                        <?php endfor; ?>

                    </div>

                </div>


                <div class="review-comment">

                    <label for="comment">
                        Ulasan
                    </label>

                    <textarea
                        id="comment"
                        name="comment"
                        rows="5"
                        placeholder="Ceritakan pengalaman Anda..."
                    ></textarea>

                </div>


                <button
                    type="submit"
                    class="confirm"
                >
                    Kirim Rating
                </button>

            </form>

        </div>

    </main>

</div>

</body>

</html>