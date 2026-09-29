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
// AMBIL DATA ROOMS
// =========================================

$stmt = $pdo->query("
    SELECT
        r.id,
        r.room_number,
        r.floor,
        r.status,
        r.created_at,

        rt.name AS room_type_name,

        h.id AS hotel_id,
        h.name AS hotel_name

    FROM rooms r

    INNER JOIN room_types rt
        ON r.room_type_id = rt.id

    INNER JOIN hotels h
        ON rt.hotel_id = h.id

    ORDER BY
        h.name ASC,
        r.floor ASC,
        r.room_number ASC
");

$rooms = $stmt->fetchAll();

// =========================================
// FLASH MESSAGE
// =========================================

$success = $_SESSION['room_success'] ?? '';
$error = $_SESSION['room_error'] ?? '';

unset($_SESSION['room_success']);
unset($_SESSION['room_error']);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manajemen Kamar - Stayora</title>

    <link
        rel="stylesheet"
        href="../../public/css/admin.css"
    >

    <style>

        .page-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            border: 0;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-edit {
            background: var(--soft);
            color: var(--primary);
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
        }

        .btn-delete {
            background: var(--danger-bg);
            color: var(--danger);
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
        }

        .actions {
            display: flex;
            gap: 7px;
        }

        .room-number {
            font-weight: 800;
        }

        .hotel-name {
            font-weight: 700;
        }

        .room-type {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 12px;
        }

        .status-available {
            background: var(--success-bg);
            color: var(--success);
        }

        .status-maintenance {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .alert {
            padding: 13px 15px;
            margin-bottom: 20px;
            border-radius: 9px;
            font-size: 13px;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success);
        }

        .alert-error {
            background: var(--danger-bg);
            color: var(--danger);
        }

    </style>

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

                <a href="../hotels/index.php">
                    Hotel
                </a>

                <a href="../room-types/index.php">
                    Tipe Kamar
                </a>

                <a href="index.php" class="active">
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

            <p class="admin-eyebrow">
                MANAJEMEN KAMAR
            </p>

            <h1>
                Kamar
            </h1>

            <p>
                Kelola kamar yang tersedia pada setiap hotel.
            </p>

        </header>


        <!-- FLASH MESSAGE -->

        <?php if ($success): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- ACTION -->

        <div class="page-actions">

            <a
                href="create.php"
                class="btn btn-primary"
            >
                + Tambah Kamar
            </a>

        </div>


        <!-- TABLE -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Daftar Kamar
                    </h2>

                    <p>
                        Daftar kamar berdasarkan hotel dan tipe kamar.
                    </p>

                </div>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                No. Kamar
                            </th>

                            <th>
                                Hotel
                            </th>

                            <th>
                                Tipe Kamar
                            </th>

                            <th>
                                Lantai
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$rooms): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="empty"
                            >
                                Belum ada kamar.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($rooms as $room): ?>

                            <tr>

                                <td>

                                    <span class="room-number">
                                        <?= htmlspecialchars(
                                            $room['room_number']
                                        ) ?>
                                    </span>

                                </td>


                                <td>

                                    <span class="hotel-name">
                                        <?= htmlspecialchars(
                                            $room['hotel_name']
                                        ) ?>
                                    </span>

                                </td>


                                <td>

                                    <span class="room-type">
                                        <?= htmlspecialchars(
                                            $room['room_type_name']
                                        ) ?>
                                    </span>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $room['floor']
                                    ) ?>

                                </td>


                                <td>

                                    <span
                                        class="status status-<?= htmlspecialchars(
                                            $room['status']
                                        ) ?>"
                                    >

                                        <?php if ($room['status'] === 'available'): ?>

                                            Tersedia

                                        <?php else: ?>

                                            Maintenance

                                        <?php endif; ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="actions">

                                        <a
                                            href="edit.php?id=<?= $room['id'] ?>"
                                            class="btn-edit"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="delete.php?id=<?= $room['id'] ?>"
                                            class="btn-delete"
                                            onclick="return confirm(
                                                'Yakin ingin menghapus kamar ini?'
                                            );"
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