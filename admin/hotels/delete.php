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
// AMBIL DATA HOTEL
// =========================================

$stmt = $pdo->prepare("
    SELECT id, name, image
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

// =========================================
// PROSES DELETE
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        // -----------------------------------------
        // HAPUS DATA HOTEL
        // -----------------------------------------

        $stmt = $pdo->prepare("
            DELETE FROM hotels
            WHERE id = ?
        ");

        $stmt->execute([$id]);


        // -----------------------------------------
        // HAPUS FOTO
        // -----------------------------------------

        if (!empty($hotel['image'])) {

            $imagePath =
                __DIR__ .
                '/../../uploads/hotels/' .
                basename($hotel['image']);

            if (
                file_exists($imagePath) &&
                is_file($imagePath)
            ) {
                unlink($imagePath);
            }
        }


        header('Location: index.php?deleted=1');
        exit;

    } catch (PDOException $e) {

        $error = 'Hotel tidak dapat dihapus karena masih digunakan oleh data lain.';
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

    <title>Hapus Hotel - Stayora</title>

    <link
        rel="stylesheet"
        href="../../public/css/admin.css"
    >

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


    <main class="admin-content">

        <header class="admin-header">

            <p class="admin-eyebrow">
                MANAJEMEN HOTEL
            </p>

            <h1>
                Hapus Hotel
            </h1>

            <p>
                Tindakan ini tidak dapat dibatalkan.
            </p>

        </header>


        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Konfirmasi Penghapusan
                    </h2>

                </div>

                <a
                    href="index.php"
                    class="panel-link"
                >
                    ← Kembali
                </a>

            </div>


            <div style="padding: 30px;">

                <?php if (isset($error)): ?>

                    <div class="alert alert-danger">

                        <?= htmlspecialchars($error) ?>

                    </div>

                    <a
                        href="index.php"
                        class="btn-secondary"
                    >
                        Kembali
                    </a>

                <?php else: ?>

                    <p>
                        Apakah Anda yakin ingin menghapus hotel:
                    </p>

                    <h2>
                        <?= htmlspecialchars($hotel['name']) ?>
                    </h2>

                    <p style="color: #667085;">
                        Data hotel beserta foto yang tersimpan di
                        <code>uploads/hotels</code>
                        akan dihapus.
                    </p>


                    <form
                        method="POST"
                        style="
                            display: flex;
                            gap: 10px;
                            margin-top: 25px;
                        "
                    >

                        <a
                            href="index.php"
                            class="btn-secondary"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="btn-danger"
                            onclick="return confirm('Yakin ingin menghapus hotel ini?')"
                        >
                            Ya, Hapus Hotel
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

</body>

</html>