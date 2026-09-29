<?php

session_start();

require_once __DIR__ . '/../../config/database.php';

// =========================================
// CEK LOGIN
// =========================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// =========================================
// CEK ROLE ADMIN
// =========================================

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

// =========================================
// AMBIL DATA HOTEL
// =========================================

$stmt = $pdo->query("
    SELECT
        id,
        name,
        location,
        description,
        rating,
        image,
        created_at
    FROM hotels
    ORDER BY created_at DESC
");

$hotels = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Hotel - Admin Stayora</title>

    <link
        rel="stylesheet"
        href="../../public/css/admin.css"
    >

</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="admin-brand">
            stay<span>ora</span>
        </div>

        <div class="admin-label">
            ADMIN PANEL
        </div>

        <nav>

            <div class="nav-section">

                <span class="nav-title">
                    UTAMA
                </span>

                <a href="../dashboard.php">
                    Dashboard
                </a>

            </div>


            <div class="nav-section">

                <span class="nav-title">
                    MANAJEMEN HOTEL
                </span>

                <a href="index.php" class="active">
                    Hotel
                </a>

                <a href="../room-types/index.php">
                    Tipe Kamar
                </a>

                <a href="../rooms/index.php">
                    Kamar
                </a>

            </div>


            <div class="nav-section">

                <span class="nav-title">
                    TRANSAKSI
                </span>

                <a href="../reservations/index.php">
                    Reservasi
                </a>

                <a href="../payments/index.php">
                    Pembayaran
                </a>

            </div>


            <div class="nav-section">

                <span class="nav-title">
                    PENGGUNA
                </span>

                <a href="../users/index.php">
                    Pengguna
                </a>

            </div>

        </nav>

        <div class="sidebar-bottom">

            <a href="../../index.php">
                ← Website
            </a>

            <a href="../../auth/logout.php">
                Logout
            </a>

        </div>

    </aside>


    <!-- CONTENT -->

    <main class="admin-content">

        <header class="admin-header">

            <div>

                <p class="admin-eyebrow">
                    MANAJEMEN HOTEL
                </p>

                <h1>
                    Hotel
                </h1>

                <p>
                    Kelola daftar hotel yang tersedia di Stayora.
                </p>

            </div>

        </header>


        <!-- HOTEL PANEL -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Daftar Hotel
                    </h2>

                    <p>
                        Hotel yang tersedia di sistem.
                    </p>

                </div>

                <a
                    href="create.php"
                    class="panel-link"
                >
                    + Tambah Hotel
                </a>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>Foto</th>

                            <th>Hotel</th>

                            <th>Lokasi</th>

                            <th>Rating</th>

                            <th>Dibuat</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$hotels): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="empty"
                            >
                                Belum ada hotel.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($hotels as $hotel): ?>

                            <tr>

                                <td>

                                    <?php if (!empty($hotel['image'])): ?>

                                        <img
                                            src="../../uploads/hotels/<?= htmlspecialchars($hotel['image']) ?>"
                                            alt="<?= htmlspecialchars($hotel['name']) ?>"
                                            style="
                                                width: 80px;
                                                height: 55px;
                                                object-fit: cover;
                                                border-radius: 8px;
                                            "
                                        >

                                    <?php else: ?>

                                        <span
                                            style="color: var(--muted);"
                                        >
                                            Tidak ada foto
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars($hotel['name']) ?>
                                    </strong>

                                    <?php if (!empty($hotel['description'])): ?>

                                        <small>
                                            <?= htmlspecialchars(
                                                mb_strimwidth(
                                                    $hotel['description'],
                                                    0,
                                                    60,
                                                    '...'
                                                )
                                            ) ?>
                                        </small>

                                    <?php endif; ?>

                                </td>


                                <td>
                                    <?= htmlspecialchars($hotel['location']) ?>
                                </td>


                                <td>

                                    <span class="status status-confirmed">
                                        <?= htmlspecialchars($hotel['rating']) ?> ★
                                    </span>

                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        date(
                                            'd-m-Y',
                                            strtotime($hotel['created_at'])
                                        )
                                    ) ?>
                                </td>


                                <td>

                                    <a
                                        href="edit.php?id=<?= $hotel['id'] ?>"
                                        class="panel-link"
                                    >
                                        Edit
                                    </a>

                                    &nbsp;

                                    <a
                                        href="delete.php?id=<?= $hotel['id'] ?>"
                                        class="panel-link"
                                        onclick="return confirm(
                                            'Yakin ingin menghapus hotel ini?'
                                        )"
                                    >
                                        Hapus
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>

</html>

