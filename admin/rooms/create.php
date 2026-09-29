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
    SELECT id, name
    FROM hotels
    ORDER BY name ASC
");

$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================
// PROSES CREATE
// =========================================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hotelId = $_POST['hotel_id'] ?? '';
    $roomNumber = trim($_POST['room_number'] ?? '');
    $roomTypeId = $_POST['room_type_id'] ?? '';
    $floor = $_POST['floor'] ?? '';
    $status = $_POST['status'] ?? 'available';

    // =========================================
    // VALIDASI
    // =========================================

    if (
        empty($hotelId) ||
        empty($roomNumber) ||
        empty($roomTypeId) ||
        $floor === ''
    ) {

        $error = 'Semua field wajib diisi.';

    } else {

        // =========================================
        // CEK NOMOR KAMAR
        // =========================================

        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM rooms
            WHERE room_number = ?
        ");

        $check->execute([$roomNumber]);

        if ($check->fetchColumn() > 0) {

            $error = 'Nomor kamar tersebut sudah digunakan.';

        } else {

            // =========================================
            // INSERT
            // =========================================

            $stmt = $pdo->prepare("
                INSERT INTO rooms (
                    hotel_id,
                    room_number,
                    room_type_id,
                    floor,
                    status
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $hotelId,
                $roomNumber,
                $roomTypeId,
                $floor,
                $status
            ]);

            header('Location: index.php');
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

    <title>Tambah Kamar - Stayora</title>

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
                Tambah Kamar
            </h1>

            <p>
                Tambahkan kamar baru ke hotel.
            </p>

        </header>


        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Informasi Kamar
                    </h2>

                    <p>
                        Masukkan informasi kamar yang ingin ditambahkan.
                    </p>

                </div>

            </div>


            <div style="padding: 24px;">

                <?php if ($error): ?>

                    <div class="status status-cancelled"
                         style="margin-bottom: 20px;">

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>


                <form method="POST">


                    <!-- HOTEL -->

                    <div class="form-group">

                        <label for="hotel_id">
                            Hotel
                        </label>

                        <select
                            name="hotel_id"
                            id="hotel_id"
                            required
                        >

                            <option value="">
                                -- Pilih Hotel --
                            </option>

                            <?php foreach ($hotels as $hotel): ?>

                                <option
                                    value="<?= $hotel['id'] ?>"
                                    <?= (
                                        ($_POST['hotel_id'] ?? '') == $hotel['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $hotel['name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- ROOM TYPE -->

                    <div class="form-group">

                        <label for="room_type_id">
                            Tipe Kamar
                        </label>

                        <select
                            name="room_type_id"
                            id="room_type_id"
                            required
                            disabled
                        >

                            <option value="">
                                -- Pilih Hotel Terlebih Dahulu --
                            </option>

                        </select>

                    </div>


                    <!-- ROOM NUMBER -->

                    <div class="form-group">

                        <label for="room_number">
                            Nomor Kamar
                        </label>

                        <input
                            type="text"
                            name="room_number"
                            id="room_number"
                            maxlength="10"
                            value="<?= htmlspecialchars(
                                $_POST['room_number'] ?? ''
                            ) ?>"
                            placeholder="Contoh: 101"
                            required
                        >

                    </div>


                    <!-- FLOOR -->

                    <div class="form-group">

                        <label for="floor">
                            Lantai
                        </label>

                        <input
                            type="number"
                            name="floor"
                            id="floor"
                            value="<?= htmlspecialchars(
                                $_POST['floor'] ?? ''
                            ) ?>"
                            placeholder="Contoh: 1"
                            required
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label for="status">
                            Status
                        </label>

                        <select
                            name="status"
                            id="status"
                        >

                            <option
                                value="available"
                                <?= (
                                    ($_POST['status'] ?? 'available')
                                    === 'available'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Tersedia
                            </option>

                            <option
                                value="maintenance"
                                <?= (
                                    ($_POST['status'] ?? '')
                                    === 'maintenance'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Maintenance
                            </option>

                        </select>

                    </div>


                    <!-- ACTION -->

                    <div
                        style="
                            display:flex;
                            gap:10px;
                            margin-top:25px;
                        "
                    >

                        <a
                            href="index.php"
                            class="panel-link"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="confirm"
                        >
                            Simpan Kamar
                        </button>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>


<script>

const hotelSelect = document.getElementById('hotel_id');
const roomTypeSelect = document.getElementById('room_type_id');

hotelSelect.addEventListener('change', function () {

    const hotelId = this.value;

    roomTypeSelect.innerHTML = '';

    if (!hotelId) {

        roomTypeSelect.disabled = true;

        roomTypeSelect.innerHTML = `
            <option value="">
                -- Pilih Hotel Terlebih Dahulu --
            </option>
        `;

        return;
    }

    roomTypeSelect.disabled = true;

    roomTypeSelect.innerHTML = `
        <option value="">
            Memuat tipe kamar...
        </option>
    `;


    fetch(`get-room-types.php?hotel_id=${hotelId}`)

        .then(response => response.json())

        .then(data => {

            roomTypeSelect.innerHTML = `
                <option value="">
                    -- Pilih Tipe Kamar --
                </option>
            `;

            if (data.length === 0) {

                roomTypeSelect.innerHTML = `
                    <option value="">
                        Belum ada tipe kamar untuk hotel ini
                    </option>
                `;

                roomTypeSelect.disabled = true;

                return;
            }


            data.forEach(roomType => {

                const option =
                    document.createElement('option');

                option.value = roomType.id;

                option.textContent = roomType.name;

                roomTypeSelect.appendChild(option);

            });

            roomTypeSelect.disabled = false;

        })

        .catch(error => {

            console.error(error);

            roomTypeSelect.innerHTML = `
                <option value="">
                    Gagal memuat tipe kamar
                </option>
            `;

            roomTypeSelect.disabled = true;

        });

});

</script>

</body>
</html>