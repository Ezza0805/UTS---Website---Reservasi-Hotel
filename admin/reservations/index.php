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
// PROSES UPDATE STATUS
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reservationId = filter_input(
        INPUT_POST,
        'reservation_id',
        FILTER_VALIDATE_INT
    );

    $action = $_POST['action'] ?? '';

    if ($reservationId && $action) {

        try {

            $pdo->beginTransaction();

            // =====================================
            // AMBIL DATA RESERVASI
            // =====================================

            $stmt = $pdo->prepare("
                SELECT
                    r.id,
                    r.room_id,
                    r.status AS reservation_status,
                    p.id AS payment_id,
                    p.status AS payment_status
                FROM reservations r
                LEFT JOIN payments p
                    ON p.reservation_id = r.id
                WHERE r.id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $reservationId
            ]);

            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                throw new Exception(
                    'Reservasi tidak ditemukan.'
                );
            }

            // =====================================
            // UPDATE STATUS RESERVASI
            // =====================================

            if ($action === 'update_reservation_status') {

                $newStatus = $_POST['reservation_status'] ?? '';

                // Status yang diperbolehkan
                $allowedStatuses = [
                    'pending',
                    'confirmed',
                    'cancelled',
                    'completed'
                ];

                if (!in_array($newStatus, $allowedStatuses, true)) {
                    throw new Exception(
                        'Status reservasi tidak valid.'
                    );
                }

                // -------------------------------------
                // UPDATE RESERVASI
                // -------------------------------------

                $stmt = $pdo->prepare("
                    UPDATE reservations
                    SET status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $newStatus,
                    $reservationId
                ]);

                // -------------------------------------
                // UPDATE STATUS KAMAR
                // -------------------------------------
                //
                // pending / confirmed
                //      -> occupied
                //
                // cancelled / completed
                //      -> available
                //
                // -------------------------------------

                if ($reservation['room_id']) {

                    if (
                        $newStatus === 'cancelled' ||
                        $newStatus === 'completed'
                    ) {

                        $stmt = $pdo->prepare("
                            UPDATE rooms
                            SET status = 'available'
                            WHERE id = ?
                        ");

                        $stmt->execute([
                            $reservation['room_id']
                        ]);

                    } elseif (
                        $newStatus === 'pending' ||
                        $newStatus === 'confirmed'
                    ) {

                        $stmt = $pdo->prepare("
                            UPDATE rooms
                            SET status = 'occupied'
                            WHERE id = ?
                        ");

                        $stmt->execute([
                            $reservation['room_id']
                        ]);
                    }
                }
            }

            // =====================================
            // UPDATE STATUS PEMBAYARAN
            // =====================================

            elseif ($action === 'update_payment_status') {

                $newPaymentStatus = $_POST['payment_status'] ?? '';

                // Status yang diperbolehkan
                $allowedPaymentStatuses = [
                    'pending',
                    'paid',
                    'failed'
                ];

                if (
                    !in_array(
                        $newPaymentStatus,
                        $allowedPaymentStatuses,
                        true
                    )
                ) {
                    throw new Exception(
                        'Status pembayaran tidak valid.'
                    );
                }

                // Pastikan pembayaran tersedia
                if (!$reservation['payment_id']) {
                    throw new Exception(
                        'Data pembayaran untuk reservasi ini tidak ditemukan.'
                    );
                }

                // -------------------------------------
                // UPDATE PEMBAYARAN
                // -------------------------------------

                $stmt = $pdo->prepare("
                    UPDATE payments
                    SET status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $newPaymentStatus,
                    $reservation['payment_id']
                ]);
            }

            // =====================================
            // AKSI TIDAK VALID
            // =====================================

            else {

                throw new Exception(
                    'Aksi tidak valid.'
                );
            }

            // =====================================
            // COMMIT
            // =====================================

            $pdo->commit();

            header(
                'Location: index.php?success=1'
            );

            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            header(
                'Location: index.php?error=' .
                urlencode($e->getMessage())
            );

            exit;
        }
    }
}

// =========================================
// AMBIL DATA RESERVASI
// =========================================

$stmt = $pdo->query("
    SELECT
        r.id,
        r.check_in,
        r.check_out,
        r.guests,
        r.total_price,
        r.status,

        u.name AS user_name,
        u.email AS user_email,

        h.name AS hotel_name,

        rm.room_number,

        rt.name AS room_type_name,

        p.method AS payment_method,
        p.status AS payment_status

    FROM reservations r

    INNER JOIN users u
        ON u.id = r.user_id

    INNER JOIN rooms rm
        ON rm.id = r.room_id

    INNER JOIN room_types rt
        ON rt.id = rm.room_type_id

    INNER JOIN hotels h
        ON h.id = rm.hotel_id

    LEFT JOIN payments p
        ON p.reservation_id = r.id

    ORDER BY r.id DESC
");

$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================
// FORMAT RUPIAH
// =========================================

function rupiah($number)
{
    return number_format(
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

    <title>
        Reservasi - Admin Stayora
    </title>

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

                <a
                    href="index.php"
                    class="active"
                >
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


    <!-- =========================================
         CONTENT
    ========================================== -->

    <main class="admin-content">

        <header class="admin-header">

            <div>

                <p class="admin-eyebrow">
                    TRANSAKSI
                </p>

                <h1>
                    Reservasi
                </h1>

                <p>
                    Kelola reservasi pelanggan,
                    pembayaran, dan status pemesanan.
                </p>

            </div>

        </header>


        <!-- =====================================
             ALERT SUCCESS
        ====================================== -->

        <?php if (isset($_GET['success'])): ?>

            <div class="panel">

                <p
                    style="
                        margin: 0;
                        color: #166534;
                    "
                >
                    Perubahan berhasil disimpan.
                </p>

            </div>

        <?php endif; ?>


        <!-- =====================================
             ALERT ERROR
        ====================================== -->

        <?php if (isset($_GET['error'])): ?>

            <div class="panel">

                <p
                    style="
                        margin: 0;
                        color: #991b1b;
                    "
                >
                    <?= htmlspecialchars($_GET['error']) ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- =====================================
             RESERVATION PANEL
        ====================================== -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Daftar Reservasi
                    </h2>

                    <p>
                        Semua reservasi pelanggan
                        yang tercatat dalam sistem.
                    </p>

                </div>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Pelanggan
                            </th>

                            <th>
                                Hotel
                            </th>

                            <th>
                                Kamar
                            </th>

                            <th>
                                Tanggal
                            </th>

                            <th>
                                Tamu
                            </th>

                            <th>
                                Total
                            </th>

                            <th>
                                Reservasi
                            </th>

                            <th>
                                Pembayaran
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$reservations): ?>

                        <tr>

                            <td
                                colspan="10"
                                class="empty"
                            >
                                Belum ada reservasi.
                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($reservations as $reservation): ?>

                            <tr>

                                <!-- =================================
                                     ID
                                ================================== -->

                                <td>

                                    <strong>
                                        #<?= $reservation['id'] ?>
                                    </strong>

                                </td>


                                <!-- =================================
                                     USER
                                ================================== -->

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $reservation['user_name']
                                        ) ?>
                                    </strong>

                                    <small>
                                        <?= htmlspecialchars(
                                            $reservation['user_email']
                                        ) ?>
                                    </small>

                                </td>


                                <!-- =================================
                                     HOTEL
                                ================================== -->

                                <td>

                                    <?= htmlspecialchars(
                                        $reservation['hotel_name']
                                    ) ?>

                                </td>


                                <!-- =================================
                                     ROOM
                                ================================== -->

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $reservation['room_number']
                                        ) ?>
                                    </strong>

                                    <small>
                                        <?= htmlspecialchars(
                                            $reservation['room_type_name']
                                        ) ?>
                                    </small>

                                </td>


                                <!-- =================================
                                     DATE
                                ================================== -->

                                <td>

                                    <?= htmlspecialchars(
                                        $reservation['check_in']
                                    ) ?>

                                    <br>

                                    →

                                    <br>

                                    <?= htmlspecialchars(
                                        $reservation['check_out']
                                    ) ?>

                                </td>


                                <!-- =================================
                                     GUEST
                                ================================== -->

                                <td>

                                    <?= (int)$reservation['guests'] ?>

                                    tamu

                                </td>


                                <!-- =================================
                                     PRICE
                                ================================== -->

                                <td>

                                    <strong>

                                        Rp
                                        <?= rupiah(
                                            $reservation['total_price']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- =================================
                                     RESERVATION STATUS
                                ================================== -->

                                <td>

                                    <?php if (
                                        $reservation['status']
                                        === 'completed'
                                    ): ?>

                                        <span class="status status-confirmed">
                                            Selesai
                                        </span>

                                    <?php elseif (
                                        $reservation['status']
                                        === 'confirmed'
                                    ): ?>

                                        <span class="status status-confirmed">
                                            Dikonfirmasi
                                        </span>

                                    <?php elseif (
                                        $reservation['status']
                                        === 'cancelled'
                                    ): ?>

                                        <span class="status status-cancelled">
                                            Dibatalkan
                                        </span>

                                    <?php else: ?>

                                        <span class="status status-pending">
                                            Pending
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- =================================
                                     PAYMENT
                                ================================== -->

                                <td>

                                    <?php if (
                                        $reservation['payment_status']
                                        === 'paid'
                                    ): ?>

                                        <span class="status status-confirmed">
                                            Sudah Dibayar
                                        </span>

                                    <?php elseif (
                                        $reservation['payment_status']
                                        === 'failed'
                                    ): ?>

                                        <span class="status status-cancelled">
                                            Gagal
                                        </span>

                                    <?php elseif (
                                        $reservation['payment_status']
                                        === 'pending'
                                    ): ?>

                                        <span class="status status-pending">
                                            Pending
                                        </span>

                                    <?php else: ?>

                                        <span
                                            style="
                                                color: var(--muted);
                                            "
                                        >
                                            Tidak ada pembayaran
                                        </span>

                                    <?php endif; ?>


                                    <small>

                                        <?= htmlspecialchars(
                                            $reservation['payment_method']
                                            ?? '-'
                                        ) ?>

                                    </small>

                                </td>


                                <!-- =================================
                                     ACTION
                                ================================== -->

                                <td>

                                    <div
                                        style="
                                            display: flex;
                                            flex-direction: column;
                                            gap: 12px;
                                            min-width: 150px;
                                        "
                                    >

                                        <!-- =========================
                                             STATUS RESERVASI
                                        ========================== -->

                                        <form
                                            method="POST"
                                            onsubmit="
                                                return confirm(
                                                    'Simpan perubahan status reservasi?'
                                                );
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="reservation_id"
                                                value="<?= $reservation['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update_reservation_status"
                                            >


                                            <label
                                                style="
                                                    display: block;
                                                    margin-bottom: 4px;
                                                    font-size: 12px;
                                                    color: var(--muted);
                                                "
                                            >
                                                Status Reservasi
                                            </label>


                                            <select
                                                name="reservation_status"
                                                style="
                                                    width: 100%;
                                                    padding: 7px;
                                                    border-radius: 6px;
                                                    border: 1px solid #ddd;
                                                "
                                            >

                                                <option
                                                    value="pending"
                                                    <?= $reservation['status'] === 'pending'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Pending
                                                </option>

                                                <option
                                                    value="confirmed"
                                                    <?= $reservation['status'] === 'confirmed'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Dikonfirmasi
                                                </option>

                                                <option
                                                    value="cancelled"
                                                    <?= $reservation['status'] === 'cancelled'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Dibatalkan
                                                </option>

                                                <option
                                                    value="completed"
                                                    <?= $reservation['status'] === 'completed'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Selesai
                                                </option>

                                            </select>


                                            <button
                                                type="submit"
                                                class="panel-link"
                                                style="
                                                    margin-top: 6px;
                                                    cursor: pointer;
                                                    border: none;
                                                    background: none;
                                                    padding: 0;
                                                "
                                            >
                                                Simpan Reservasi
                                            </button>

                                        </form>


                                        <!-- =========================
                                             STATUS PEMBAYARAN
                                        ========================== -->

                                        <?php if (
                                            $reservation['payment_status'] !== null
                                        ): ?>

                                            <form
                                                method="POST"
                                                onsubmit="
                                                    return confirm(
                                                        'Simpan perubahan status pembayaran?'
                                                    );
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="reservation_id"
                                                    value="<?= $reservation['id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="update_payment_status"
                                                >


                                                <label
                                                    style="
                                                        display: block;
                                                        margin-bottom: 4px;
                                                        font-size: 12px;
                                                        color: var(--muted);
                                                    "
                                                >
                                                    Status Pembayaran
                                                </label>


                                                <select
                                                    name="payment_status"
                                                    style="
                                                        width: 100%;
                                                        padding: 7px;
                                                        border-radius: 6px;
                                                        border: 1px solid #ddd;
                                                    "
                                                >

                                                    <option
                                                        value="pending"
                                                        <?= $reservation['payment_status'] === 'pending'
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        Pending
                                                    </option>

                                                    <option
                                                        value="paid"
                                                        <?= $reservation['payment_status'] === 'paid'
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        Paid
                                                    </option>

                                                    <option
                                                        value="failed"
                                                        <?= $reservation['payment_status'] === 'failed'
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        Failed
                                                    </option>

                                                </select>


                                                <button
                                                    type="submit"
                                                    class="panel-link"
                                                    style="
                                                        margin-top: 6px;
                                                        cursor: pointer;
                                                        border: none;
                                                        background: none;
                                                        padding: 0;
                                                    "
                                                >
                                                    Simpan Pembayaran
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <small
                                                style="
                                                    color: var(--muted);
                                                "
                                            >
                                                Data pembayaran belum tersedia.
                                            </small>

                                        <?php endif; ?>

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