<?php

session_start();

$isLoggedIn = isset($_SESSION['user_id']);

$hotels = [
    [
        "id" => 1,
        "name" => "Grand Ternate Hotel",
        "location" => "Ternate Tengah",
        "rating" => 4.8,
        "price" => 650000,
        "image_class" => ""
    ],
    [
        "id" => 2,
        "name" => "Emerald Bay Resort",
        "location" => "Ternate Selatan",
        "rating" => 4.7,
        "price" => 820000,
        "image_class" => "two"
    ],
    [
        "id" => 3,
        "name" => "Kaisar Boutique Hotel",
        "location" => "Ternate Utara",
        "rating" => 4.6,
        "price" => 540000,
        "image_class" => "three"
    ]
];

$destination = $_GET["destination"] ?? "Ternate";
$sort = $_GET["sort"] ?? "recommended";

if ($sort === "price") {
    usort($hotels, fn($a, $b) => $a["price"] <=> $b["price"]);
} elseif ($sort === "rating") {
    usort($hotels, fn($a, $b) => $b["rating"] <=> $a["rating"]);
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
    <title>Stayora — Reservasi Hotel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <header class="nav">
        <div class="brand">stay<span>ora</span></div>
        <nav class="navlinks">
            <a href="index.php">Hotel</a>
            <a href="#promo">Promo</a>
            <a href="#pesanan">Pesanan</a>
            <a href="#bantuan">Bantuan</a>
        </nav>
        <?php if ($isLoggedIn): ?>

            <span class="profile">
                Halo, <?= htmlspecialchars($_SESSION['user_name']) ?>
            </span>

            <a class="profile" href="auth/logout.php">
                Logout
            </a>

        <?php else: ?>

            <a class="profile" href="auth/login.php">
                Masuk
            </a>

        <?php endif; ?>
    </header>

    <section class="hero">
        <div class="hero-inner">
            <div class="eyebrow">Reservasi hotel lebih sederhana</div>
            <h1>Temukan tempat menginap yang terasa seperti rumah.</h1>
            <p>Cari hotel, pilih kamar, lalu lakukan reservasi dalam beberapa langkah.</p>

            <form class="searchbox" method="GET" action="index.php">
                <div class="field">
                    <label>DESTINASI</label>
                    <input name="destination" value="<?= htmlspecialchars($destination) ?>" placeholder="Kota atau hotel">
                </div>

                <div class="field">
                    <label>CHECK-IN</label>
                    <input name="checkin" type="date" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="field">
                    <label>CHECK-OUT</label>
                    <input name="checkout" type="date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>

                <div class="field">
                    <label>TAMU</label>
                    <select name="guests">
                        <option>2 tamu</option>
                        <option>1 tamu</option>
                        <option>3 tamu</option>
                        <option>4 tamu</option>
                    </select>
                </div>

                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <button class="searchbtn" type="submit">Cari</button>
            </form>
        </div>
    </section>

    <main class="main">
        <div class="sectionhead">
            <div>
                <h2>Hotel pilihan</h2>
                <p>Menampilkan hotel populer di <?= htmlspecialchars($destination) ?></p>
            </div>

            <form method="GET">
                <input type="hidden" name="destination" value="<?= htmlspecialchars($destination) ?>">
                <select class="sort" name="sort" onchange="this.form.submit()">
                    <option value="recommended" <?= $sort === "recommended" ? "selected" : "" ?>>Rekomendasi</option>
                    <option value="price" <?= $sort === "price" ? "selected" : "" ?>>Harga terendah</option>
                    <option value="rating" <?= $sort === "rating" ? "selected" : "" ?>>Rating tertinggi</option>
                </select>
            </form>
        </div>

        <div class="cards">
            <?php foreach ($hotels as $hotel): ?>
                <article class="card">
                    <div class="photo <?= $hotel["image_class"] ?>">
                        <span class="badge"><?= $hotel["rating"] ?> ★</span>
                    </div>

                    <div class="body">
                        <div class="rating">Sangat baik · <?= $hotel["rating"] ?></div>
                        <h3><?= htmlspecialchars($hotel["name"]) ?></h3>
                        <div class="location">📍 <?= htmlspecialchars($hotel["location"]) ?></div>

                        <div class="price">
                            <div>
                                <strong>Rp <?= rupiah($hotel["price"]) ?></strong>
                                <small>/ malam</small>
                            </div>

                            <a class="book" href="booking.php?hotel_id=<?= $hotel["id"] ?>">Pesan</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>
