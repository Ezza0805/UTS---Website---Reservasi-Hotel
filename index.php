<?php

session_start();

require_once __DIR__ . '/config/database.php';

$isLoggedIn = isset($_SESSION['user_id']);


// =========================================
// PENGATURAN PENCARIAN
// =========================================

$destination = trim($_GET['destination'] ?? '');
$sort = $_GET['sort'] ?? 'recommended';


// =========================================
// QUERY HOTEL
// Rating dihitung dari hotel_reviews
// =========================================

$sql = "
    SELECT
        h.id,
        h.name,
        h.location,
        h.description,
        h.address,
        h.phone,
        h.image,
        h.created_at,

        COALESCE(
            ROUND(AVG(hr.rating), 1),
            0
        ) AS rating

    FROM hotels h

    LEFT JOIN hotel_reviews hr
        ON hr.hotel_id = h.id
";

$params = [];


// =========================================
// FILTER DESTINASI
// =========================================

if ($destination !== '') {

    $sql .= "
        WHERE
            h.name LIKE ?
            OR h.location LIKE ?
    ";

    $search = '%' . $destination . '%';

    $params[] = $search;
    $params[] = $search;
}


// =========================================
// GROUP BY
// =========================================

$sql .= "
    GROUP BY
        h.id,
        h.name,
        h.location,
        h.description,
        h.address,
        h.phone,
        h.image,
        h.created_at
";


// =========================================
// SORTING
// =========================================

if ($sort === 'rating') {

    // Rating tertinggi
    $sql .= "
        ORDER BY rating DESC
    ";

} elseif ($sort === 'price') {

    /*
     * Saat ini tabel hotels belum memiliki harga.
     *
     * Harga kamar nantinya berasal dari room_types.
     * Untuk sementara kita gunakan urutan nama hotel.
     */

    $sql .= "
        ORDER BY h.name ASC
    ";

} else {

    /*
     * Rekomendasi:
     * hotel terbaru ditampilkan terlebih dahulu.
     */

    $sql .= "
        ORDER BY h.created_at DESC
    ";
}


// =========================================
// EKSEKUSI QUERY
// =========================================

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$hotels = $stmt->fetchAll();


// =========================================
// FORMAT RUPIAH
// =========================================

function rupiah($number)
{
    return number_format(
        $number,
        0,
        ",",
        "."
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
        Stayora — Reservasi Hotel
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

            <a href="#promo">
                Promo
            </a>

            <a href="pesanan.php">
                Pesanan
            </a>

            <a href="#bantuan">
                Bantuan
            </a>

        </nav>


        <?php if ($isLoggedIn): ?>

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



    <!-- =====================================
         HERO
    ====================================== -->

    <section class="hero">

        <div class="hero-inner">

            <div class="eyebrow">

                Reservasi hotel lebih sederhana

            </div>


            <h1>

                Temukan tempat menginap
                yang terasa seperti rumah.

            </h1>


            <p>

                Cari hotel, pilih kamar, lalu lakukan reservasi
                dalam beberapa langkah.

            </p>



            <form
                class="searchbox"
                method="GET"
                action="index.php"
            >

                <!-- DESTINASI -->

                <div class="field">

                    <label>
                        DESTINASI
                    </label>

                    <input
                        name="destination"
                        value="<?= htmlspecialchars($destination) ?>"
                        placeholder="Kota atau hotel"
                    >

                </div>



                <!-- CHECK IN -->

                <div class="field">

                    <label>
                        CHECK-IN
                    </label>

                    <input
                        name="checkin"
                        type="date"
                        value="<?= htmlspecialchars(
                            $_GET['checkin']
                            ?? date('Y-m-d')
                        ) ?>"
                    >

                </div>



                <!-- CHECK OUT -->

                <div class="field">

                    <label>
                        CHECK-OUT
                    </label>

                    <input
                        name="checkout"
                        type="date"
                        value="<?= htmlspecialchars(
                            $_GET['checkout']
                            ?? date(
                                'Y-m-d',
                                strtotime('+1 day')
                            )
                        ) ?>"
                    >

                </div>



                <!-- TAMU -->

                <div class="field">

                    <label>
                        TAMU
                    </label>

                    <select name="guests">

                        <option value="1">

                            1 tamu

                        </option>


                        <option
                            value="2"
                            <?= ($_GET['guests'] ?? '2') === '2'
                                ? 'selected'
                                : '' ?>
                        >

                            2 tamu

                        </option>


                        <option value="3">

                            3 tamu

                        </option>


                        <option value="4">

                            4 tamu

                        </option>

                    </select>

                </div>



                <input
                    type="hidden"
                    name="sort"
                    value="<?= htmlspecialchars($sort) ?>"
                >



                <button
                    class="searchbtn"
                    type="submit"
                >

                    Cari

                </button>

            </form>

        </div>

    </section>



    <!-- =====================================
         DAFTAR HOTEL
    ====================================== -->

    <main class="main">


        <div class="sectionhead">

            <div>

                <h2>

                    Hotel pilihan

                </h2>


                <p>

                    <?php if ($destination !== ''): ?>

                        Menampilkan hotel di

                        <?= htmlspecialchars($destination) ?>

                    <?php else: ?>

                        Menampilkan hotel yang tersedia

                    <?php endif; ?>

                </p>

            </div>



            <!-- SORT -->

            <form method="GET">

                <input
                    type="hidden"
                    name="destination"
                    value="<?= htmlspecialchars($destination) ?>"
                >


                <select
                    class="sort"
                    name="sort"
                    onchange="this.form.submit()"
                >

                    <option
                        value="recommended"
                        <?= $sort === 'recommended'
                            ? 'selected'
                            : '' ?>
                    >

                        Terbaru

                    </option>


                    <option
                        value="rating"
                        <?= $sort === 'rating'
                            ? 'selected'
                            : '' ?>
                    >

                        Rating tertinggi

                    </option>


                    <option
                        value="price"
                        <?= $sort === 'price'
                            ? 'selected'
                            : '' ?>
                    >

                        Harga terendah

                    </option>

                </select>

            </form>

        </div>



        <!-- =====================================
             HOTEL CARDS
        ====================================== -->

        <div class="cards">


            <?php if (!$hotels): ?>

                <div class="empty">

                    <h3>

                        Hotel tidak ditemukan

                    </h3>

                    <p>

                        Belum ada hotel yang sesuai
                        dengan pencarian Anda.

                    </p>

                </div>


            <?php else: ?>


                <?php foreach ($hotels as $hotel): ?>

                    <article class="card">


                        <!-- FOTO HOTEL -->

                        <?php if (!empty($hotel['image'])): ?>

                            <div
                                class="photo"
                                style="
                                    background-image:
                                    url('<?= htmlspecialchars(
                                        $hotel['image']
                                    ) ?>');
                                    background-size: cover;
                                    background-position: center;
                                "
                            >

                                <?php if (!empty($hotel['image'])): ?>

                                    <img
                                        src="uploads/hotels/<?= htmlspecialchars(
                                            $hotel['image']
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $hotel['name']
                                        ) ?>"
                                    >

                                <?php endif; ?>


                                <!-- RATING DI FOTO -->

                                <span class="badge">

                                    <?= htmlspecialchars(
                                        $hotel['rating']
                                    ) ?>

                                    ★

                                </span>

                            </div>


                        <?php else: ?>

                            <div class="photo">

                                <span class="badge">

                                    <?= htmlspecialchars(
                                        $hotel['rating']
                                    ) ?>

                                    ★

                                </span>

                            </div>

                        <?php endif; ?>



                        <!-- INFORMASI HOTEL -->

                        <div class="body">


                            <!-- RATING -->

                            <div class="rating">

                                Sangat baik ·

                                <?= htmlspecialchars(
                                    $hotel['rating']
                                ) ?>

                                ★

                            </div>



                            <!-- NAMA HOTEL -->

                            <h3>

                                <?= htmlspecialchars(
                                    $hotel['name']
                                ) ?>

                            </h3>



                            <!-- LOKASI -->

                            <div class="location">

                                📍

                                <?= htmlspecialchars(
                                    $hotel['location']
                                ) ?>

                            </div>



                            <!-- DESKRIPSI -->

                            <?php if (!empty($hotel['description'])): ?>

                                <p class="hotel-description">

                                    <?= htmlspecialchars(
                                        $hotel['description']
                                    ) ?>

                                </p>

                            <?php endif; ?>



                            <!-- HARGA / PESAN -->

                            <div class="price">

                                <div>

                                    <strong>

                                        Lihat kamar

                                    </strong>

                                    <small>

                                        tersedia

                                    </small>

                                </div>


                                <a
                                    class="book"
                                    href="hotel.php?hotel_id=<?= $hotel['id'] ?>&checkin=<?= urlencode(
                                        $_GET['checkin']
                                        ?? date('Y-m-d')
                                    ) ?>&checkout=<?= urlencode(
                                        $_GET['checkout']
                                        ?? date(
                                            'Y-m-d',
                                            strtotime('+1 day')
                                        )
                                    ) ?>&guests=<?= urlencode(
                                        $_GET['guests']
                                        ?? '2'
                                    ) ?>"
                                >

                                    Pesan

                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>


            <?php endif; ?>

        </div>

    </main>

</div>

</body>

</html>