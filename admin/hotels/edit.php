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
// CEK ID HOTEL
// =========================================

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

// =========================================
// AMBIL DATA HOTEL
// =========================================

$stmt = $pdo->prepare("
    SELECT *
    FROM hotels
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$hotel = $stmt->fetch();

if (!$hotel) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// =========================================
// PROSES UPDATE
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // -----------------------------------------
    // VALIDASI
    // -----------------------------------------

    if ($name === '' || $location === '') {

        $error = 'Nama hotel dan lokasi wajib diisi.';

    } else {

        $imageName = $hotel['image'];

        // -----------------------------------------
        // CEK FOTO BARU
        // -----------------------------------------

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

                $error = 'Foto hotel gagal diupload.';

            } else {

                $allowedTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];

                $fileType = mime_content_type(
                    $_FILES['image']['tmp_name']
                );

                if (!in_array($fileType, $allowedTypes, true)) {

                    $error = 'Format foto harus JPG, PNG, atau WEBP.';

                } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {

                    $error = 'Ukuran foto maksimal 5 MB.';

                } else {

                    $extension = match ($fileType) {
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                    };

                    $newFileName =
                        'hotel_' .
                        $id .
                        '_' .
                        time() .
                        '.' .
                        $extension;

                    $uploadDir = __DIR__ . '/../../uploads/hotels/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $destination = $uploadDir . $newFileName;

                    if (
                        move_uploaded_file(
                            $_FILES['image']['tmp_name'],
                            $destination
                        )
                    ) {

                        // -----------------------------------------
                        // HAPUS FOTO LAMA
                        // -----------------------------------------

                        if (!empty($hotel['image'])) {

                            $oldImage =
                                $uploadDir .
                                basename($hotel['image']);

                            if (
                                file_exists($oldImage) &&
                                is_file($oldImage)
                            ) {
                                unlink($oldImage);
                            }
                        }

                        $imageName = $newFileName;

                    } else {

                        $error = 'Gagal menyimpan foto hotel.';
                    }
                }
            }
        }

        // -----------------------------------------
        // UPDATE DATABASE
        // -----------------------------------------

        if ($error === '') {

            $stmt = $pdo->prepare("
                UPDATE hotels
                SET
                    name = ?,
                    location = ?,
                    description = ?,
                    image = ?,
                    address = ?,
                    phone = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $location,
                $description,
                $imageName,
                $address,
                $phone,
                $id
            ]);

            header('Location: index.php?updated=1');
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

    <title>Edit Hotel - Stayora</title>

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

            <p class="admin-eyebrow">
                MANAJEMEN HOTEL
            </p>

            <h1>
                Edit Hotel
            </h1>

            <p>
                Perbarui informasi hotel.
            </p>

        </header>


        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Informasi Hotel
                    </h2>

                    <p>
                        Ubah data hotel yang diperlukan.
                    </p>

                </div>

                <a
                    href="index.php"
                    class="panel-link"
                >
                    ← Kembali
                </a>

            </div>


            <div class="form-container">

                <?php if ($error): ?>

                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="admin-form"
                >

                    <div class="form-group">

                        <label for="name">
                            Nama Hotel
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($hotel['name']) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="location">
                            Lokasi
                        </label>

                        <input
                            type="text"
                            id="location"
                            name="location"
                            value="<?= htmlspecialchars($hotel['location']) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="description">
                            Deskripsi
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                        ><?= htmlspecialchars($hotel['description'] ?? '') ?></textarea>

                    </div>


                    <?php if (!empty($hotel['image'])): ?>

                        <div class="form-group">

                            <label>
                                Foto Saat Ini
                            </label>

                            <img
                                src="../../uploads/hotels/<?= htmlspecialchars($hotel['image']) ?>"
                                alt="<?= htmlspecialchars($hotel['name']) ?>"
                                style="
                                    width: 220px;
                                    height: 140px;
                                    object-fit: cover;
                                    border-radius: 12px;
                                    border: 1px solid #e5e7eb;
                                "
                            >

                        </div>

                    <?php endif; ?>


                    <div class="form-group">

                        <label for="image">
                            Ganti Foto Hotel
                        </label>

                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <small>
                            Kosongkan jika tidak ingin mengganti foto.
                            Maksimal 5 MB.
                        </small>

                    </div>


                    <div class="form-group">

                        <label for="address">
                            Alamat
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            rows="4"
                        ><?= htmlspecialchars($hotel['address'] ?? '') ?></textarea>

                    </div>


                    <div class="form-group">

                        <label for="phone">
                            Nomor Telepon
                        </label>

                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="<?= htmlspecialchars($hotel['phone'] ?? '') ?>"
                        >

                    </div>


                    <div class="form-actions">

                        <a
                            href="index.php"
                            class="btn-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn-primary"
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