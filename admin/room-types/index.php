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
// AMBIL DATA ROOM TYPES
// =========================================

$stmt = $pdo->query("
    SELECT
        rt.id,
        rt.name,
        rt.description,
        rt.capacity,
        rt.price,
        rt.image,
        rt.created_at,

        h.name AS hotel_name

    FROM room_types rt

    INNER JOIN hotels h
        ON rt.hotel_id = h.id

    ORDER BY rt.created_at DESC
");

$roomTypes = $stmt->fetchAll();

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

    <title>Tipe Kamar - Stayora</title>

    <link
        rel="stylesheet"
        href="../../public/css/admin.css"
    >

</head>

<body>

<div class="admin-layout">

    <!-- =========================================
         SIDEBAR
    ========================================== -->

    <aside class="sidebar">

        <div class="admin-brand">
            stay<span>ora</span>
        </div>

        <div class="admin-label">
            ADMIN PANEL
        </div>

        <nav>

            <!-- UTAMA -->

            <div class="nav-section">

                <span class="nav-title">
                    UTAMA
                </span>

                <a href="../dashboard.php">
                    Dashboard
                </a>

            </div>


            <!-- MANAJEMEN HOTEL -->

            <div class="nav-section">

                <span class="nav-title">
                    MANAJEMEN HOTEL
                </span>

                <a href="../hotels/index.php">
                    Hotel
                </a>

                <a
                    href="index.php"
                    class="active"
                >
                    Tipe Kamar
                </a>

                <a href="../rooms/index.php">
                    Kamar
                </a>

            </div>


            <!-- TRANSAKSI -->

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


            <!-- PENGGUNA -->

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


    <!-- =========================================
         CONTENT
    ========================================== -->

    <main class="admin-content">

        <header class="admin-header">

            <p class="admin-eyebrow">
                MANAJEMEN KAMAR
            </p>

            <h1>
                Tipe Kamar
            </h1>

            <p>
                Kelola tipe kamar yang tersedia pada setiap hotel.
            </p>

        </header>


        <!-- =========================================
             PANEL
        ========================================== -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Daftar Tipe Kamar
                    </h2>

                    <p>
                        Semua tipe kamar yang terdaftar pada Stayora.
                    </p>

                </div>


                <a
                    href="create.php"
                    class="btn-primary"
                >
                    + Tambah Tipe Kamar
                </a>

            </div>


            <!-- =========================================
                 TABLE
            ========================================== -->

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Foto
                            </th>

                            <th>
                                Hotel
                            </th>

                            <th>
                                Tipe Kamar
                            </th>

                            <th>
                                Kapasitas
                            </th>

                            <th>
                                Harga
                            </th>

                            <th>
                                Dibuat
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$roomTypes): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="empty"
                            >
                                Belum ada tipe kamar.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($roomTypes as $roomType): ?>

                            <tr>

                                <!-- FOTO -->

                                <td>

                                    <?php if (!empty($roomType['image'])): ?>

                                        <img
                                            src="../../uploads/room-types/<?= htmlspecialchars($roomType['image']) ?>"
                                            alt="<?= htmlspecialchars($roomType['name']) ?>"
                                            style="
                                                width: 70px;
                                                height: 50px;
                                                object-fit: cover;
                                                border-radius: 8px;
                                                border: 1px solid #e5e7eb;
                                            "
                                        >

                                    <?php else: ?>

                                        <span
                                            style="
                                                color: #98a2b3;
                                                font-size: 12px;
                                            "
                                        >
                                            Tidak ada foto
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- HOTEL -->

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $roomType['hotel_name']
                                        ) ?>
                                    </strong>

                                </td>


                                <!-- TIPE KAMAR -->

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $roomType['name']
                                        ) ?>
                                    </strong>

                                    <?php if (!empty($roomType['description'])): ?>

                                        <small>
                                            <?= htmlspecialchars(
                                                $roomType['description']
                                            ) ?>
                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- KAPASITAS -->

                                <td>

                                    <?= htmlspecialchars(
                                        $roomType['capacity']
                                    ) ?>

                                    tamu

                                </td>


                                <!-- HARGA -->

                                <td>

                                    <?= rupiah(
                                        $roomType['price']
                                    ) ?>

                                    <small>
                                        / malam
                                    </small>

                                </td>


                                <!-- TANGGAL -->

                                <td>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $roomType['created_at']
                                        )
                                    ) ?>

                                </td>


                                <!-- AKSI -->

                                <td>

                                    <div
                                        style="
                                            display: flex;
                                            gap: 7px;
                                            align-items: center;
                                        "
                                    >

                                        <a
                                            href="edit.php?id=<?= $roomType['id'] ?>"
                                            class="btn-secondary"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="delete.php?id=<?= $roomType['id'] ?>"
                                            class="btn-danger"
                                            onclick="
                                                return confirm(
                                                    'Yakin ingin menghapus tipe kamar ini?'
                                                );
                                            "
                                        >
                                            Hapus
                                        </a>

                                    </div>

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