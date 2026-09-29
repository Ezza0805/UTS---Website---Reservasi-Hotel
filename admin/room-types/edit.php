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

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// =========================================
// AMBIL DATA ROOM TYPE
// =========================================

$stmt = $pdo->prepare("
    SELECT *
    FROM room_types
    WHERE id = ?
");

$stmt->execute([$id]);

$roomType = $stmt->fetch();

if (!$roomType) {
    header('Location: index.php');
    exit;
}

// =========================================
// AMBIL HOTEL
// =========================================

$stmt = $pdo->query("
    SELECT id, name
    FROM hotels
    ORDER BY name ASC
");

$hotels = $stmt->fetchAll();

// =========================================
// PROSES FORM
// =========================================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hotelId = (int) ($_POST['hotel_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);

    if ($hotelId <= 0) {

        $error = 'Hotel harus dipilih.';

    } elseif ($name === '') {

        $error = 'Nama tipe kamar wajib diisi.';

    } elseif ($capacity <= 0) {

        $error = 'Kapasitas harus lebih dari 0.';

    } elseif ($price <= 0) {

        $error = 'Harga harus lebih dari 0.';

    } else {

        // =================================
        // VALIDASI HOTEL
        // =================================

        $stmt = $pdo->prepare("
            SELECT id
            FROM hotels
            WHERE id = ?
        ");

        $stmt->execute([$hotelId]);

        if (!$stmt->fetch()) {

            $error = 'Hotel tidak ditemukan.';

        } else {

            $imageName = $roomType['image'];

            // =================================
            // UPLOAD IMAGE BARU
            // =================================

            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

                    $error = 'Gagal mengupload gambar.';

                } else {

                    $allowedTypes = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp'
                    ];

                    $fileType = mime_content_type(
                        $_FILES['image']['tmp_name']
                    );

                    $fileSize = $_FILES['image']['size'];

                    if (!isset($allowedTypes[$fileType])) {

                        $error = 'Format gambar harus JPG, PNG, atau WEBP.';

                    } elseif ($fileSize > 5 * 1024 * 1024) {

                        $error = 'Ukuran gambar maksimal 5 MB.';

                    } else {

                        $extension = $allowedTypes[$fileType];

                        $newImageName =
                            'room_' .
                            time() .
                            '_' .
                            bin2hex(random_bytes(5)) .
                            '.' .
                            $extension;

                        $uploadDir =
                            __DIR__ .
                            '/../../uploads/room-types/';

                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $uploadPath =
                            $uploadDir .
                            $newImageName;

                        if (
                            move_uploaded_file(
                                $_FILES['image']['tmp_name'],
                                $uploadPath
                            )
                        ) {

                            // Hapus gambar lama
                            if (
                                !empty($roomType['image']) &&
                                file_exists(
                                    $uploadDir . $roomType['image']
                                )
                            ) {
                                unlink(
                                    $uploadDir . $roomType['image']
                                );
                            }

                            $imageName = $newImageName;

                        } else {

                            $error = 'Gagal menyimpan gambar.';
                        }
                    }
                }
            }

            // =================================
            // UPDATE
            // =================================

            if ($error === '') {

                $stmt = $pdo->prepare("
                    UPDATE room_types
                    SET
                        hotel_id = ?,
                        name = ?,
                        description = ?,
                        capacity = ?,
                        price = ?,
                        image = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $hotelId,
                    $name,
                    $description !== '' ? $description : null,
                    $capacity,
                    $price,
                    $imageName,
                    $id
                ]);

                header('Location: index.php');
                exit;
            }
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

    <title>Edit Tipe Kamar - Stayora</title>

    <link
        rel="stylesheet"
        href="../../public/css/admin.css"
    >

    <style>
        .form-panel {
            max-width: 800px;
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: white;
            color: var(--text);
            font: inherit;
            outline: none;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-help {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;
        }

        .current-image {
            margin-top: 10px;
        }

        .current-image img {
            width: 180px;
            height: 110px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid var(--line);
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

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #f2f4f7;
            color: var(--text);
        }

        .alert {
            padding: 13px 15px;
            margin-bottom: 20px;
            border-radius: 9px;
            background: var(--danger-bg);
            color: var(--danger);
            font-size: 13px;
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
                <span class="nav-title">UTAMA</span>

                <a href="../dashboard.php">
                    Dashboard
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-title">MANAJEMEN HOTEL</span>

                <a href="../hotels/index.php">
                    Hotel
                </a>

                <a href="index.php" class="active">
                    Tipe Kamar
                </a>

                <a href="../rooms/index.php">
                    Kamar
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-title">TRANSAKSI</span>

                <a href="../reservations/index.php">
                    Reservasi
                </a>

                <a href="../payments/index.php">
                    Pembayaran
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-title">PENGGUNA</span>

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
                MANAJEMEN TIPE KAMAR
            </p>

            <h1>
                Edit Tipe Kamar
            </h1>

            <p>
                Perbarui informasi tipe kamar.
            </p>

        </header>


        <section class="panel form-panel">

            <div class="panel-header">

                <div>
                    <h2>
                        Informasi Tipe Kamar
                    </h2>

                    <p>
                        Ubah data yang diperlukan.
                    </p>
                </div>

            </div>


            <div class="form-body">

                <?php if ($error): ?>

                    <div class="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <div class="form-group">

                        <label for="hotel_id">
                            Hotel
                        </label>

                        <select
                            name="hotel_id"
                            id="hotel_id"
                            required
                        >

                            <?php foreach ($hotels as $hotel): ?>

                                <option
                                    value="<?= $hotel['id'] ?>"
                                    <?= (
                                        ($_POST['hotel_id'] ?? $roomType['hotel_id'])
                                        == $hotel['id']
                                    ) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($hotel['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="name">
                            Nama Tipe Kamar
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            maxlength="50"
                            value="<?= htmlspecialchars(
                                $_POST['name'] ?? $roomType['name']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="description">
                            Deskripsi
                        </label>

                        <textarea
                            name="description"
                            id="description"
                        ><?= htmlspecialchars(
                            $_POST['description'] ?? $roomType['description']
                        ) ?></textarea>

                    </div>


                    <div class="form-group">

                        <label for="capacity">
                            Kapasitas
                        </label>

                        <input
                            type="number"
                            name="capacity"
                            id="capacity"
                            min="1"
                            value="<?= htmlspecialchars(
                                $_POST['capacity'] ?? $roomType['capacity']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="price">
                            Harga per malam
                        </label>

                        <input
                            type="number"
                            name="price"
                            id="price"
                            min="0"
                            step="0.01"
                            value="<?= htmlspecialchars(
                                $_POST['price'] ?? $roomType['price']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="image">
                            Foto Kamar
                        </label>

                        <input
                            type="file"
                            name="image"
                            id="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <span class="form-help">
                            Kosongkan jika tidak ingin mengganti foto.
                        </span>

                        <?php if (!empty($roomType['image'])): ?>

                            <div class="current-image">

                                <img
                                    src="../../uploads/room-types/<?= htmlspecialchars(
                                        $roomType['image']
                                    ) ?>"
                                    alt="Foto kamar"
                                >

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="form-actions">

                        <a
                            href="index.php"
                            class="btn btn-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

</body>

</html>