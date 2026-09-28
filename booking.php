<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$hotels = [
    1 => ["name" => "Grand Ternate Hotel", "location" => "Ternate Tengah", "rating" => 4.8, "price" => 650000],
    2 => ["name" => "Emerald Bay Resort", "location" => "Ternate Selatan", "rating" => 4.7, "price" => 820000],
    3 => ["name" => "Kaisar Boutique Hotel", "location" => "Ternate Utara", "rating" => 4.6, "price" => 540000]
];

$hotelId = (int)($_GET["hotel_id"] ?? 0);

if (!isset($hotels[$hotelId])) {
    header("Location: index.php");
    exit;
}

$hotel = $hotels[$hotelId];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $success = true;
}
function rupiah($number) {
    return number_format($number, 0, ",", ".");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi — <?= htmlspecialchars($hotel["name"]) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <header class="nav">
        <div class="brand">stay<span>ora</span></div>
        <a class="back" href="index.php">← Kembali ke hotel</a>
    </header>

    <main class="booking-page">
        <div class="booking-card">
            <div class="booking-summary">
                <span class="badge"><?= $hotel["rating"] ?> ★</span>
                <h1><?= htmlspecialchars($hotel["name"]) ?></h1>
                <p>📍 <?= htmlspecialchars($hotel["location"]) ?></p>
                <div class="booking-price">
                    <strong>Rp <?= rupiah($hotel["price"]) ?></strong>
                    <span>/ malam</span>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="success">
                    <h2>Reservasi berhasil!</h2>
                    <p>Data reservasi Anda sudah diterima.</p>
                    <a class="confirm" href="index.php">Kembali ke halaman hotel</a>
                </div>
            <?php else: ?>
                <form method="POST" class="reservation-form">
                    <h2>Detail Reservasi</h2>

                    <label>Nama Lengkap</label>
                    <input type="text" name="name" required placeholder="Masukkan nama Anda">

                    <label>Email</label>
                    <input type="email" name="email" required placeholder="nama@email.com">

                    <label>Nomor Telepon</label>
                    <input type="tel" name="phone" required placeholder="08xxxxxxxxxx">

                    <div class="form-row">
                        <div>
                            <label>Check-in</label>
                            <input type="date" name="checkin" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label>Check-out</label>
                            <input type="date" name="checkout" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                    </div>

                    <label>Jumlah Tamu</label>
                    <select name="guests">
                        <option value="1">1 tamu</option>
                        <option value="2" selected>2 tamu</option>
                        <option value="3">3 tamu</option>
                        <option value="4">4 tamu</option>
                    </select>

                    <button class="confirm" type="submit">Konfirmasi Reservasi</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
