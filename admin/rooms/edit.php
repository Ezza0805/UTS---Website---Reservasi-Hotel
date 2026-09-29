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
// CEK ID
// =========================================

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

// =========================================
// AMBIL DATA KAMAR
// =========================================

$stmt = $pdo->prepare("
    SELECT *
    FROM rooms
    WHERE id = ?
");

$stmt->execute([$id]);

$room = $stmt->fetch();

if (!$room) {
    header('Location: index.php');
    exit;
}

// =========================================
// AMBIL DATA HOTEL
// =========================================

$stmt = $pdo->query("
    SELECT id, name
    FROM hotels
    ORDER BY name ASC
");

$hotels = $stmt->fetchAll();

// =========================================
// DATA FORM
// =========================================

$hotelId = $_POST['hotel_id'] ?? $room['hotel_id'];
$roomTypeId = $_POST['room_type_id'] ?? $room['room_type_id'];
$roomNumber = $_POST['room_number'] ?? $room['room_number'];
$floor = $_POST['floor'] ?? $room['floor'];
$status = $_POST['status'] ?? $room['status'];

$error = '';

// =========================================
// AMBIL TIPE KAMAR
// =========================================

$stmt = $pdo->prepare("
    SELECT id, name
    FROM room_types
    WHERE hotel_id = ?
    ORDER BY name ASC
");

$stmt->execute([(int)$hotelId]);

$roomTypes = $stmt->fetchAll();

// =========================================
// PROSES UPDATE
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hotelId = (int) $hotelId;
    $roomTypeId = (int) $roomTypeId;
    $roomNumber = trim($roomNumber);
    $floor = (int) $floor;

    // -----------------------------------------
    // VALIDASI
    // -----------------------------------------

    if ($hotelId <= 0) {
        $error = 'Silakan pilih hotel.';
    } elseif ($roomTypeId <= 0) {
        $error = 'Silakan pilih tipe kamar.';
    } elseif ($roomNumber === '') {
        $error = 'Nomor kamar wajib diisi.';
    } elseif ($floor < 1) {
        $error = 'Lantai harus lebih besar dari 0.';
    } elseif (!in_array($status, ['available', 'maintenance'], true)) {
        $error = 'Status kamar tidak valid.';
    }

    // -----------------------------------------
    // CEK TIPE KAMAR
    // -----------------------------------------

    if ($error === '') {

        $stmt = $pdo->prepare("
            SELECT id
            FROM room_types
            WHERE id = ?
            AND hotel_id = ?
        ");

        $stmt->execute([
            $roomTypeId,
            $hotelId
        ]);

        if (!$stmt->fetch()) {
            $error = 'Tipe kamar tidak sesuai dengan hotel.';
        }
    }

    // -----------------------------------------
    // CEK NOMOR KAMAR
    // -----------------------------------------

    if ($error === '') {

        $stmt = $pdo->prepare("
            SELECT id
            FROM rooms
            WHERE room_number = ?
            AND id != ?
        ");

        $stmt->execute([
            $roomNumber,
            $id
        ]);

        if ($stmt->fetch()) {
            $error = 'Nomor kamar tersebut sudah digunakan.';
        }
    }

    // -----------------------------------------
    // UPDATE
    // -----------------------------------------

    if ($error === '') {

        $stmt = $pdo->prepare("
            UPDATE rooms
            SET
                hotel_id = ?,
                room_number = ?,
                room_type_id = ?,
                floor = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $hotelId,
            $roomNumber,
            $roomTypeId,
            $floor,
            $status,
            $id
        ]);

        header('Location: index.php');
        exit;
    }

    // refresh room types apabila POST gagal
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM room_types
        WHERE hotel_id = ?
        ORDER BY name ASC
    ");

    $stmt->execute([$hotelId]);

    $roomTypes = $stmt->fetchAll();
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

    <title>Edit Kamar - Stayora</title>

    <link
        rel="stylesheet"
        href="../../public/css/admin.css"
    >

    <style>

        .form-panel {
            max-width: 760px;
        }

        .form-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: var(--surface);
            color: var(--text);
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .error-message {
            margin-bottom: 20px;
            padding: 12px 15px;
            border-radius: 9px;
            background: var(--danger-bg);
            color: var(--danger);
            font-size: 13px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 11px 17px;
            border-radius: 9px;
            border: 0;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-secondary {
            background: #f2f4f7;
            color: var(--text);
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>

<div class="admin-layout">

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


    <main class="admin-content">

        <header class="admin-header">

            <p class="admin-eyebrow">
                MANAJEMEN KAMAR
            </p>

            <h1>
                Edit Kamar
            </h1>

            <p>
                Perbarui informasi kamar.
            </p>

        </header>


        <section class="panel form-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Informasi Kamar
                    </h2>

                    <p>
                        Ubah data kamar yang diperlukan.
                    </p>

                </div>

            </div>


            <div class="form-body">

                <?php if ($error): ?>

                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endif; ?>


                <form method="POST">

                    <div class="form-group">

                        <label for="hotel_id">
                            Hotel
                        </label>

                        <select
                            name="hotel_id"
                            id="hotel_id"
                            required
                            onchange="changeHotel()"
                        >

                            <option value="">
                                -- Pilih Hotel --
                            </option>

                            <?php foreach ($hotels as $hotel): ?>

                                <option
                                    value="<?= $hotel['id'] ?>"
                                    <?= (int)$hotelId === (int)$hotel['id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($hotel['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="room_type_id">
                            Tipe Kamar
                        </label>

                        <select
                            name="room_type_id"
                            id="room_type_id"
                            required
                        >

                            <option value="">
                                -- Pilih Tipe Kamar --
                            </option>

                            <?php foreach ($roomTypes as $roomType): ?>

                                <option
                                    value="<?= $roomType['id'] ?>"
                                    <?= (int)$roomTypeId === (int)$roomType['id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($roomType['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="room_number">
                                Nomor Kamar
                            </label>

                            <input
                                type="text"
                                name="room_number"
                                id="room_number"
                                maxlength="10"
                                value="<?= htmlspecialchars($roomNumber) ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="floor">
                                Lantai
                            </label>

                            <input
                                type="number"
                                name="floor"
                                id="floor"
                                min="1"
                                value="<?= htmlspecialchars($floor) ?>"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="status">
                            Status
                        </label>

                        <select
                            name="status"
                            id="status"
                            required
                        >

                            <option
                                value="available"
                                <?= $status === 'available' ? 'selected' : '' ?>
                            >
                                Tersedia
                            </option>

                            <option
                                value="maintenance"
                                <?= $status === 'maintenance' ? 'selected' : '' ?>
                            >
                                Maintenance
                            </option>

                        </select>

                    </div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Simpan Perubahan
                        </button>

                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >
                            Batal
                        </a>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>


<script>

function changeHotel() {

    const hotelId =
        document.getElementById('hotel_id').value;

    if (hotelId === '') {
        window.location.href = 'edit.php?id=<?= $id ?>';
        return;
    }

    window.location.href =
        'edit.php?id=<?= $id ?>&hotel_id=' +
        encodeURIComponent(hotelId);
}

</script>

</body>

</html>