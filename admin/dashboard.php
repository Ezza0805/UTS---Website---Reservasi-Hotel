<?php

session_start();

require_once __DIR__ . '/../config/database.php';

// =========================================
// CEK LOGIN
// =========================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// =========================================
// CEK ROLE ADMIN
// =========================================

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// =========================================
// STATISTIK
// =========================================

$totalHotels = $pdo
    ->query("SELECT COUNT(*) FROM hotels")
    ->fetchColumn();

$totalUsers = $pdo
    ->query("SELECT COUNT(*) FROM users WHERE role = 'user'")
    ->fetchColumn();

$totalRoomTypes = $pdo
    ->query("SELECT COUNT(*) FROM room_types")
    ->fetchColumn();

$totalRooms = $pdo
    ->query("SELECT COUNT(*) FROM rooms")
    ->fetchColumn();

$totalReservations = $pdo
    ->query("SELECT COUNT(*) FROM reservations")
    ->fetchColumn();

$pendingReservations = $pdo
    ->query("
        SELECT COUNT(*)
        FROM reservations
        WHERE status = 'pending'
    ")
    ->fetchColumn();

$paidPayments = $pdo
    ->query("
        SELECT COUNT(*)
        FROM payments
        WHERE status = 'paid'
    ")
    ->fetchColumn();


// =========================================
// RESERVASI TERBARU
// =========================================

$stmt = $pdo->query("
    SELECT
        r.id,
        u.name AS user_name,
        rt.name AS room_type,
        rm.room_number,
        r.check_in,
        r.check_out,
        r.total_price,
        r.status
    FROM reservations r

    INNER JOIN users u
        ON r.user_id = u.id

    INNER JOIN rooms rm
        ON r.room_id = rm.id

    INNER JOIN room_types rt
        ON rm.room_type_id = rt.id

    ORDER BY r.created_at DESC
    LIMIT 5
");

$recentReservations = $stmt->fetchAll();


// =========================================
// FORMAT RUPIAH
// =========================================

function rupiah($number)
{
    return 'Rp ' . number_format(
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

    <title>Dashboard Admin - Stayora</title>

    <link
        rel="stylesheet"
        href="../public/css/admin.css"
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
        <span class="nav-title">UTAMA</span>

        <a href="dashboard.php" class="active">
            Dashboard
        </a>
    </div>


    <div class="nav-section">
        <span class="nav-title">MANAJEMEN HOTEL</span>

        <a href="hotels/index.php">
            Hotel
        </a>

        <a href="room-types/index.php">
            Tipe Kamar
        </a>

        <a href="rooms/index.php">
            Kamar
        </a>
    </div>


    <div class="nav-section">
        <span class="nav-title">TRANSAKSI</span>

        <a href="reservations/index.php">
            Reservasi
        </a>

        <a href="payments/index.php">
            Pembayaran
        </a>
    </div>


    <div class="nav-section">
        <span class="nav-title">PENGGUNA</span>

        <a href="users/index.php">
            Pengguna
        </a>
    </div>

</nav>

        <div class="sidebar-bottom">

            <a href="../index.php">
                ← Website
            </a>

            <a href="../auth/logout.php">
                Logout
            </a>

        </div>

    </aside>


    <!-- CONTENT -->

    <main class="admin-content">

        <header class="admin-header">

            <div>

                <p class="admin-eyebrow">
                    ADMIN DASHBOARD
                </p>

                <h1>
                    Selamat datang,
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </h1>

                <p>
                    Kelola reservasi dan kamar Stayora dari sini.
                </p>

            </div>

        </header>


        <!-- STATISTICS -->

        <section class="stats-grid">

            <div class="stat-card">

                <span>Total Hotel</span>

                <strong>
                    <?= $totalHotels ?>
                </strong>
            </div>

            <div class="stat-card">

                <span>Total Pengguna</span>

                <strong>
                    <?= $totalUsers ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>Tipe Kamar</span>

                <strong>
                    <?= $totalRoomTypes ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>Total Kamar</span>

                <strong>
                    <?= $totalRooms ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>Reservasi</span>

                <strong>
                    <?= $totalReservations ?>
                </strong>

            </div>


            <div class="stat-card warning">

                <span>Menunggu</span>

                <strong>
                    <?= $pendingReservations ?>
                </strong>

            </div>


            <div class="stat-card success">

                <span>Pembayaran Lunas</span>

                <strong>
                    <?= $paidPayments ?>
                </strong>

            </div>

        </section>


        <!-- RECENT RESERVATIONS -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Reservasi Terbaru
                    </h2>

                    <p>
                        Lima reservasi terakhir yang masuk.
                    </p>

                </div>

                <a
                    href="reservations/index.php"
                    class="panel-link"
                >
                    Lihat semua
                </a>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Pengguna</th>

                            <th>Kamar</th>

                            <th>Check-in</th>

                            <th>Check-out</th>

                            <th>Total</th>

                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (!$recentReservations): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="empty"
                            >
                                Belum ada reservasi.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentReservations as $reservation): ?>

                            <tr>

                                <td>
                                    #<?= $reservation['id'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $reservation['user_name']
                                    ) ?>
                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $reservation['room_type']
                                    ) ?>

                                    <small>
                                        Kamar
                                        <?= htmlspecialchars(
                                            $reservation['room_number']
                                        ) ?>
                                    </small>

                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $reservation['check_in']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $reservation['check_out']
                                    ) ?>
                                </td>

                                <td>
                                    <?= rupiah(
                                        $reservation['total_price']
                                    ) ?>
                                </td>

                                <td>

                                    <span
                                        class="status status-<?= htmlspecialchars(
                                            $reservation['status']
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $reservation['status']
                                            )
                                        ) ?>
                                    </span>

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