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
// CEK APAKAH SUDAH DIGUNAKAN ROOMS
// =========================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM rooms
    WHERE room_type_id = ?
");

$stmt->execute([$id]);

$totalRoomsUsingType = $stmt->fetchColumn();

if ($totalRoomsUsingType > 0) {

    $_SESSION['room_type_error'] =
        'Tipe kamar tidak dapat dihapus karena masih digunakan oleh ' .
        $totalRoomsUsingType .
        ' kamar.';

    header('Location: index.php');
    exit;
}

// =========================================
// HAPUS DATA
// =========================================

try {

    $pdo->beginTransaction();

    // Hapus database
    $stmt = $pdo->prepare("
        DELETE FROM room_types
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    // Hapus gambar
    if (!empty($roomType['image'])) {

        $imagePath =
            __DIR__ .
            '/../../uploads/room-types/' .
            $roomType['image'];

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    $pdo->commit();

    $_SESSION['room_type_success'] =
        'Tipe kamar berhasil dihapus.';

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['room_type_error'] =
        'Tipe kamar tidak dapat dihapus karena masih digunakan oleh data lain.';
}

header('Location: index.php');
exit;