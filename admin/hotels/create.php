<?php

session_start();

require_once __DIR__ . '/../../config/database.php';


// =====================================================
// CEK LOGIN
// =====================================================

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}


// =====================================================
// CEK ROLE ADMIN
// =====================================================

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}


// =====================================================
// VARIABLE
// =====================================================

$error = '';

$name        = '';
$location    = '';
$description = '';
$address     = '';
$phone       = '';


// =====================================================
// PROSES FORM
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------------------------------------------------
    // AMBIL DATA FORM
    // -------------------------------------------------

    $name        = trim($_POST['name'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');


    // -------------------------------------------------
    // VALIDASI
    // -------------------------------------------------

    if ($name === '') {

        $error = 'Nama hotel wajib diisi.';

    } elseif ($location === '') {

        $error = 'Lokasi hotel wajib diisi.';

    }


    // -------------------------------------------------
    // UPLOAD FOTO
    // -------------------------------------------------

    $imageName = null;

    if (
        $error === '' &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

            $error = 'Terjadi kesalahan saat mengupload foto.';

        } else {

            $filePath = $_FILES['image']['tmp_name'];
            $fileSize = $_FILES['image']['size'];

            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $fileType = mime_content_type($filePath);


            // Maksimal 5 MB
            if ($fileSize > 5 * 1024 * 1024) {

                $error = 'Ukuran foto maksimal 5 MB.';

            } elseif (!in_array($fileType, $allowedTypes, true)) {

                $error = 'Format foto harus JPG, PNG, atau WEBP.';

            } else {

                // -----------------------------------------
                // BUAT NAMA FILE UNIK
                // -----------------------------------------

                $extension = strtolower(
                    pathinfo(
                        $_FILES['image']['name'],
                        PATHINFO_EXTENSION
                    )
                );

                $imageName =
                    'hotel_' .
                    time() .
                    '_' .
                    bin2hex(random_bytes(4)) .
                    '.' .
                    $extension;


                // -----------------------------------------
                // FOLDER UPLOAD
                // -----------------------------------------

                $uploadDir = __DIR__ . '/../../uploads/hotels/';

                if (!is_dir($uploadDir)) {

                    mkdir(
                        $uploadDir,
                        0755,
                        true
                    );
                }


                // -----------------------------------------
                // SIMPAN FILE
                // -----------------------------------------

                $uploadPath = $uploadDir . $imageName;

                if (!move_uploaded_file(
                    $filePath,
                    $uploadPath
                )) {

                    $error = 'Foto gagal disimpan.';

                    $imageName = null;
                }
            }
        }
    }


    // -------------------------------------------------
    // SIMPAN DATA HOTEL
    // -------------------------------------------------

    if ($error === '') {

        $stmt = $pdo->prepare("
            INSERT INTO hotels (
                name,
                location,
                description,
                address,
                phone,
                image
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $location,
            $description,
            $address,
            $phone,
            $imageName
        ]);


        // -----------------------------------------
        // REDIRECT SETELAH BERHASIL
        // -----------------------------------------

        header('Location: index.php?success=created');
        exit;
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

<title>Tambah Hotel - Stayora</title>

<link
    rel="stylesheet"
    href="../../public/css/admin.css"
>

</head>

<body>

<div class="admin-layout">

<!-- =================================================
     SIDEBAR
================================================== -->

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

            <a
                href="index.php"
                class="active"
            >
                Hotel
            </a>

            <a href="../room-types/index.php">
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


    <!-- SIDEBAR BOTTOM -->

    <div class="sidebar-bottom">

        <a href="../../index.php">
            ← Website
        </a>

        <a href="../../auth/logout.php">
            Logout
        </a>

    </div>

</aside>


<!-- =================================================
     CONTENT
================================================== -->

<main class="admin-content">


    <!-- HEADER -->

    <header class="admin-header">

        <p class="admin-eyebrow">
            MANAJEMEN HOTEL
        </p>

        <h1>
            Tambah Hotel
        </h1>

        <p>
            Tambahkan informasi hotel baru ke Stayora.
        </p>

    </header>


    <!-- ERROR -->

    <?php if ($error): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- FORM PANEL -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Informasi Hotel
                </h2>

                <p>
                    Isi informasi hotel dan foto utama.
                </p>

            </div>

            <a
                href="index.php"
                class="panel-link"
            >
                ← Kembali
            </a>

        </div>


        <!-- FORM -->

        <form
            method="POST"
            enctype="multipart/form-data"
            class="admin-form"
        >


            <!-- NAMA HOTEL -->

            <div class="form-group">

                <label for="name">
                    Nama Hotel
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($name) ?>"
                    placeholder="Contoh: Grand Ternate Hotel"
                    required
                >

            </div>


            <!-- LOKASI & TELEPON -->

            <div class="form-row">

                <div class="form-group">

                    <label for="location">
                        Lokasi
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        value="<?= htmlspecialchars($location) ?>"
                        placeholder="Contoh: Ternate Tengah"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="phone">
                        Nomor Telepon
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars($phone) ?>"
                        placeholder="Contoh: 08123456789"
                    >

                </div>

            </div>


            <!-- ALAMAT -->

            <div class="form-group">

                <label for="address">
                    Alamat Lengkap
                </label>

                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    placeholder="Masukkan alamat lengkap hotel"
                ><?= htmlspecialchars($address) ?></textarea>

            </div>


            <!-- DESKRIPSI -->

            <div class="form-group">

                <label for="description">
                    Deskripsi Hotel
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    placeholder="Masukkan deskripsi hotel"
                ><?= htmlspecialchars($description) ?></textarea>

            </div>


            <!-- FOTO -->

            <div class="form-group">

                <label for="image">
                    Foto Hotel
                </label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <small>
                    JPG, PNG, atau WEBP. Maksimal 5 MB.
                </small>

            </div>


            <!-- ACTION -->

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
                    Simpan Hotel
                </button>

            </div>

        </form>

    </section>

</main>


</div>

</body>

</html>
