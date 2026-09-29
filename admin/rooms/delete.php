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
// HANYA POST
// =========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// =========================================
// CEK ID
// =========================================

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    header('Location: index.php?error=' . urlencode(
        'ID kamar tidak valid.'
    ));
    exit;
}

try {

    // =========================================
    // CEK APAKAH KAMAR ADA
    // =========================================

    $stmt = $pdo->prepare("
        SELECT id
        FROM rooms
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    if (!$stmt->fetch()) {

        header('Location: index.php?error=' . urlencode(
            'Kamar tidak ditemukan.'
        ));

        exit;
    }


    // =========================================
    // CEK RIWAYAT RESERVASI
    // =========================================

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM reservations
        WHERE room_id = ?
    ");

    $stmt->execute([$id]);

    $reservationCount = $stmt->fetchColumn();


    if ($reservationCount > 0) {

        header('Location: index.php?error=' . urlencode(
            'Kamar tidak dapat dihapus karena sudah memiliki riwayat reservasi.'
        ));

        exit;
    }


    // =========================================
    // DELETE KAMAR
    // =========================================

    $stmt = $pdo->prepare("
        DELETE FROM rooms
        WHERE id = ?
    ");

    $stmt->execute([$id]);


    // =========================================
    // BERHASIL
    // =========================================

    header('Location: index.php?success=' . urlencode(
        'Kamar berhasil dihapus.'
    ));

    exit;


} catch (PDOException $e) {

    // =========================================
    // ERROR DATABASE
    // =========================================

    header('Location: index.php?error=' . urlencode(
        'Kamar gagal dihapus.'
    ));

    exit;
}