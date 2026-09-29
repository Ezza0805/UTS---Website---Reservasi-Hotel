<?php

session_start();

require_once __DIR__ . '/config/database.php';

// =========================================
// CEK LOGIN
// =========================================

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}


// =========================================
// AMBIL HOTEL ID
// =========================================

$hotelId = filter_input(
    INPUT_GET,
    'hotel_id',
    FILTER_VALIDATE_INT
);

if (!$hotelId) {
    header('Location: index.php');
    exit;
}


// =========================================
// AMBIL DATA HOTEL
// =========================================

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        location,
        description,
        address,
        phone,
        rating,
        image
    FROM hotels
    WHERE id = ?
");

$stmt->execute([$hotelId]);

$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    header('Location: index.php');
    exit;
}


// =========================================
// AMBIL TIPE KAMAR HOTEL
// =========================================

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        description,
        capacity,
        price,
        image
    FROM room_types
    WHERE hotel_id = ?
    ORDER BY price ASC
");

$stmt->execute([$hotelId]);

$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// =========================================
// VARIABLE
// =========================================

$error = '';

$totalPrice = 0;
$nights = 0;

$checkin = $_POST['checkin']
    ?? date('Y-m-d');

$checkout = $_POST['checkout']
    ?? date(
        'Y-m-d',
        strtotime('+1 day')
    );

$guests = $_POST['guests']
    ?? '';

$roomTypeId = $_POST['room_type_id']
    ?? '';

$paymentMethod = $_POST['payment_method']
    ?? '';


// =========================================
// PROSES RESERVASI
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =========================================
    // AMBIL DATA POST
    // =========================================

    $checkin = $_POST['checkin'] ?? '';

    $checkout = $_POST['checkout'] ?? '';

    $guests = filter_input(
        INPUT_POST,
        'guests',
        FILTER_VALIDATE_INT
    );

    $roomTypeId = filter_input(
        INPUT_POST,
        'room_type_id',
        FILTER_VALIDATE_INT
    );

    $paymentMethod = $_POST['payment_method']
        ?? '';


    // =========================================
    // VALIDASI DASAR
    // =========================================

    if (
        !$checkin ||
        !$checkout ||
        !$guests ||
        !$roomTypeId ||
        !$paymentMethod
    ) {

        $error =
            'Semua data reservasi wajib diisi.';

    } elseif ($checkout <= $checkin) {

        $error =
            'Tanggal check-out harus setelah check-in.';

    } elseif ($guests < 1) {

        $error =
            'Jumlah tamu tidak valid.';

    } elseif (!in_array(
        $paymentMethod,
        [
            'bank_transfer',
            'qris',
            'cash'
        ],
        true
    )) {

        $error =
            'Metode pembayaran tidak valid.';

    } else {

        // =========================================
        // HITUNG JUMLAH MALAM
        // =========================================

        try {

            $checkinDate =
                new DateTime($checkin);

            $checkoutDate =
                new DateTime($checkout);

            $nights =
                $checkinDate
                    ->diff($checkoutDate)
                    ->days;

        } catch (Exception $e) {

            $error =
                'Format tanggal tidak valid.';
        }


        // =========================================
        // VALIDASI JUMLAH MALAM
        // =========================================

        if (!$error && $nights < 1) {

            $error =
                'Jumlah malam tidak valid.';
        }


        // =========================================
        // PROSES JIKA VALID
        // =========================================

        if (!$error) {

            // =====================================
            // AMBIL TIPE KAMAR
            // =====================================

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    hotel_id,
                    name,
                    capacity,
                    price
                FROM room_types
                WHERE id = ?
                  AND hotel_id = ?
            ");

            $stmt->execute([
                $roomTypeId,
                $hotelId
            ]);

            $roomType =
                $stmt->fetch(PDO::FETCH_ASSOC);


            // =====================================
            // CEK TIPE KAMAR
            // =====================================

            if (!$roomType) {

                $error =
                    'Tipe kamar tidak ditemukan.';

            } elseif (
                $guests >
                (int)$roomType['capacity']
            ) {

                $error =
                    'Jumlah tamu melebihi kapasitas tipe kamar yang dipilih.';

            } else {

                // =================================
                // CARI KAMAR TERSEDIA
                // =================================

                $stmt = $pdo->prepare("
                    SELECT
                        rm.id,
                        rm.room_number
                    FROM rooms rm

                    WHERE rm.hotel_id = ?

                      AND rm.room_type_id = ?

                      AND rm.status = 'available'

                      AND NOT EXISTS (

                          SELECT 1

                          FROM reservations r

                          WHERE r.room_id = rm.id

                            AND r.status != 'cancelled'

                            AND r.check_in < ?

                            AND r.check_out > ?

                      )

                    ORDER BY rm.room_number ASC

                    LIMIT 1
                ");

                $stmt->execute([
                    $hotelId,
                    $roomTypeId,
                    $checkout,
                    $checkin
                ]);

                $room =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                // =================================
                // CEK KETERSEDIAAN
                // =================================

                if (!$room) {

                    $error =
                        'Tidak ada kamar yang tersedia untuk tipe kamar tersebut pada tanggal yang dipilih.';

                } else {

                    // =================================
                    // HITUNG TOTAL
                    // =================================

                    $totalPrice =
                        (float)$roomType['price']
                        * $nights;


                    // =================================
                    // TRANSACTION
                    // =================================

                    try {

                        $pdo->beginTransaction();


                        // =================================
                        // INSERT RESERVATION
                        // =================================

                        $stmt = $pdo->prepare("
                            INSERT INTO reservations (
                                user_id,
                                room_id,
                                check_in,
                                check_out,
                                guests,
                                total_price,
                                status
                            )
                            VALUES (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                ?,
                                'pending'
                            )
                        ");

                        $stmt->execute([
                            $_SESSION['user_id'],
                            $room['id'],
                            $checkin,
                            $checkout,
                            $guests,
                            $totalPrice
                        ]);


                        $reservationId =
                            $pdo->lastInsertId();


                        // =================================
                        // INSERT PAYMENT
                        // =================================

                        $stmt = $pdo->prepare("
                            INSERT INTO payments (
                                reservation_id,
                                amount,
                                method,
                                status
                            )
                            VALUES (
                                ?,
                                ?,
                                ?,
                                'pending'
                            )
                        ");

                        $stmt->execute([
                            $reservationId,
                            $totalPrice,
                            $paymentMethod
                        ]);


                        // =================================
                        // COMMIT
                        // =================================
                        
                        $pdo->commit();


                        // =================================
                        // REDIRECT KE PAYMENT
                        // =================================

                        header(
                            'Location: payment.php?reservation_id='
                            . $reservationId
                        );

                        exit;


                    } catch (PDOException $e) {

                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        $error =
                            'Reservasi gagal diproses. Silakan coba lagi.';
                    }
                }
            }
        }
    }
}


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
        Reservasi —
        <?= htmlspecialchars($hotel['name']) ?>
    </title>

    <link
        rel="stylesheet"
        href="public/css/style.css"
    >

</head>


<body>

<div class="app">


    <!-- =========================================
         NAVBAR
    ========================================== -->

    <header class="nav">

        <div class="brand">
            stay<span>ora</span>
        </div>

        <a
            class="back"
            href="index.php"
        >
            ← Kembali ke hotel
        </a>

    </header>


    <!-- =========================================
         CONTENT
    ========================================== -->

    <main class="booking-page">

        <div class="booking-card">


            <!-- =====================================
                 HOTEL SUMMARY
            ====================================== -->

            <div class="booking-summary">


                <?php if (!empty($hotel['image'])): ?>

                    <img
                        class="booking-hotel-image"
                        src="uploads/hotels/<?= htmlspecialchars($hotel['image']) ?>"
                        alt="<?= htmlspecialchars($hotel['name']) ?>"
                    >

                <?php endif; ?>


                <?php if (
                    (float)$hotel['rating'] > 0
                ): ?>

                    <span class="badge">

                        <?= htmlspecialchars(
                            $hotel['rating']
                        ) ?>

                        ★

                    </span>

                <?php endif; ?>


                <h1>

                    <?= htmlspecialchars(
                        $hotel['name']
                    ) ?>

                </h1>


                <p>

                    📍

                    <?= htmlspecialchars(
                        $hotel['location']
                    ) ?>

                </p>


            </div>


            <!-- =====================================
                 ERROR
            ====================================== -->

            <?php if ($error): ?>

                <div
                    class="status status-cancelled"
                    style="margin-bottom:20px;"
                >

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- =====================================
                 FORM
            ====================================== -->

            <form
                method="POST"
                class="reservation-form"
            >


                <h2>
                    Detail Reservasi
                </h2>


                <!-- =============================
                     CHECK-IN / CHECK-OUT
                ============================== -->

                <div class="form-row">


                    <div>

                        <label>
                            Check-in
                        </label>

                        <input
                            type="date"
                            name="checkin"
                            value="<?= htmlspecialchars(
                                $checkin
                            ) ?>"
                            min="<?= date(
                                'Y-m-d'
                            ) ?>"
                            required
                        >

                    </div>


                    <div>

                        <label>
                            Check-out
                        </label>

                        <input
                            type="date"
                            name="checkout"
                            value="<?= htmlspecialchars(
                                $checkout
                            ) ?>"
                            min="<?= date(
                                'Y-m-d',
                                strtotime('+1 day')
                            ) ?>"
                            required
                        >

                    </div>


                </div>


                <!-- =============================
                     TIPE KAMAR
                ============================== -->

                <label>
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


                    <?php foreach (
                        $roomTypes
                        as $roomType
                    ): ?>


                        <option
                            value="<?= $roomType['id'] ?>"
                            data-capacity="<?= (int)$roomType['capacity'] ?>"
                            data-price="<?= (float)$roomType['price'] ?>"
                            <?= $roomTypeId == $roomType['id']
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars(
                                $roomType['name']
                            ) ?>

                            —

                            Rp
                            <?= rupiah(
                                $roomType['price']
                            ) ?>

                            / malam

                            —

                            <?= (int)$roomType['capacity'] ?>

                            tamu

                        </option>


                    <?php endforeach; ?>


                </select>


                <?php if (!$roomTypes): ?>

                    <p>
                        Belum ada tipe kamar untuk hotel ini.
                    </p>

                <?php endif; ?>


                <!-- =============================
                     JUMLAH TAMU
                ============================== -->

                <label>
                    Jumlah Tamu
                </label>


                <select
                    name="guests"
                    id="guests"
                    required
                >

                    <option value="">
                        -- Pilih tipe kamar terlebih dahulu --
                    </option>

                </select>


                <!-- =============================
                     RINGKASAN HARGA
                ============================== -->

                <div
                    id="price-summary"
                    style="
                        display:none;
                        margin-top:15px;
                        padding:18px;
                        background:#f8f9ff;
                        border-radius:12px;
                    "
                >

                    <strong>
                        Ringkasan Reservasi
                    </strong>


                    <div style="margin-top:10px;">

                        <span>
                            Harga kamar:
                        </span>

                        <strong id="room-price">
                            Rp 0
                        </strong>

                    </div>


                    <div>

                        <span>
                            Jumlah malam:
                        </span>

                        <strong id="night-count">
                            0 malam
                        </strong>

                    </div>


                    <div
                        style="
                            margin-top:10px;
                            padding-top:10px;
                            border-top:1px solid #e5e7eb;
                        "
                    >

                        <span>
                            Total:
                        </span>

                        <strong id="total-price">
                            Rp 0
                        </strong>

                    </div>


                </div>


                <!-- =============================
                     METODE PEMBAYARAN
                ============================== -->

                <label>
                    Metode Pembayaran
                </label>


                <select
                    name="payment_method"
                    required
                >

                    <option value="">
                        -- Pilih Metode Pembayaran --
                    </option>


                    <option
                        value="bank_transfer"
                        <?= $paymentMethod === 'bank_transfer'
                            ? 'selected'
                            : '' ?>
                    >
                        Bank Transfer
                    </option>


                    <option
                        value="qris"
                        <?= $paymentMethod === 'qris'
                            ? 'selected'
                            : '' ?>
                    >
                        QRIS
                    </option>


                    <option
                        value="cash"
                        <?= $paymentMethod === 'cash'
                            ? 'selected'
                            : '' ?>
                    >
                        Cash
                    </option>


                </select>


                <!-- =============================
                     SUBMIT
                ============================== -->

                <button
                    class="confirm"
                    type="submit"
                >
                    Lanjut ke Pembayaran
                </button>


            </form>


        </div>

    </main>


</div>


<!-- =============================================
     JAVASCRIPT
============================================= -->

<script>

const roomTypeSelect =
    document.getElementById('room_type_id');

const guestsSelect =
    document.getElementById('guests');

const checkinInput =
    document.querySelector(
        'input[name="checkin"]'
    );

const checkoutInput =
    document.querySelector(
        'input[name="checkout"]'
    );

const priceSummary =
    document.getElementById(
        'price-summary'
    );

const roomPriceElement =
    document.getElementById(
        'room-price'
    );

const nightCountElement =
    document.getElementById(
        'night-count'
    );

const totalPriceElement =
    document.getElementById(
        'total-price'
    );


// =========================================
// FORMAT RUPIAH
// =========================================

function formatRupiah(number) {

    return new Intl.NumberFormat(
        'id-ID'
    ).format(number);

}


// =========================================
// UPDATE GUEST OPTIONS
// =========================================

function updateGuestOptions() {

    const selectedOption =
        roomTypeSelect.options[
            roomTypeSelect.selectedIndex
        ];


    const capacity =
        parseInt(
            selectedOption.dataset.capacity || 0
        );


    const currentGuests =
        <?= json_encode(
            (int)$guests
        ) ?>;


    guestsSelect.innerHTML = '';


    if (!capacity) {

        const option =
            document.createElement(
                'option'
            );

        option.value = '';

        option.textContent =
            '-- Pilih tipe kamar terlebih dahulu --';

        guestsSelect.appendChild(
            option
        );

        priceSummary.style.display =
            'none';

        return;
    }


    const placeholder =
        document.createElement(
            'option'
        );

    placeholder.value = '';

    placeholder.textContent =
        '-- Pilih jumlah tamu --';

    guestsSelect.appendChild(
        placeholder
    );


    for (
        let i = 1;
        i <= capacity;
        i++
    ) {

        const option =
            document.createElement(
                'option'
            );

        option.value = i;

        option.textContent =
            i + ' tamu';


        if (
            i === currentGuests
        ) {

            option.selected =
                true;

        }


        guestsSelect.appendChild(
            option
        );

    }


    updatePriceSummary();

}


// =========================================
// HITUNG HARGA
// =========================================

function updatePriceSummary() {

    const selectedOption =
        roomTypeSelect.options[
            roomTypeSelect.selectedIndex
        ];


    const price =
        parseFloat(
            selectedOption.dataset.price || 0
        );


    const checkin =
        new Date(
            checkinInput.value
        );

    const checkout =
        new Date(
            checkoutInput.value
        );


    if (
        !price ||
        !checkinInput.value ||
        !checkoutInput.value
    ) {

        priceSummary.style.display =
            'none';

        return;

    }


    const difference =
        checkout - checkin;


    const nights =
        Math.ceil(
            difference /
            (1000 * 60 * 60 * 24)
        );


    if (nights <= 0) {

        priceSummary.style.display =
            'none';

        return;

    }


    const total =
        price * nights;


    roomPriceElement.textContent =
        'Rp ' +
        formatRupiah(price) +
        ' / malam';


    nightCountElement.textContent =
        nights +
        ' malam';


    totalPriceElement.textContent =
        'Rp ' +
        formatRupiah(total);


    priceSummary.style.display =
        'block';

}


// =========================================
// EVENT
// =========================================

roomTypeSelect.addEventListener(
    'change',
    function () {

        updateGuestOptions();

    }
);


checkinInput.addEventListener(
    'change',
    updatePriceSummary
);


checkoutInput.addEventListener(
    'change',
    updatePriceSummary
);


// =========================================
// INITIAL
// =========================================

updateGuestOptions();

updatePriceSummary();

</script>


</body>

</html>