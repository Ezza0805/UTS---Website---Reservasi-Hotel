<?php session_start(); 
require_once __DIR__ . '/../config/database.php'; 
if (isset($_SESSION['user_id'])) {
        header('Location: ../index.php'); 
        exit; 
    } 
    $error = ''; 
    $success = ''; 
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($name === '' || $email === '' || $password === '') {
            $error = 'Semua field wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $stmt = $pdo->prepare( 'SELECT id FROM users WHERE email = ? LIMIT 1' );
            $stmt->execute([$email]);
            if ($stmt->fetch()) { $error = 'Email sudah terdaftar.'; } else {
                $hashedPassword = password_hash( $password, PASSWORD_DEFAULT );
                $stmt = $pdo->prepare( 'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)' );
                $stmt->execute([ $name, $email, $hashedPassword, 'user' ]);
                $_SESSION['success'] = 'Registrasi berhasil. Silakan login.';
                header('Location: login.php');
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

<title>Register - Hotel Reservation</title>

<link rel="stylesheet" href="../public/css/style.css">

</head>

<body>

<main class="auth-container">

    <a href="../index.php" class="back-home">
        ← Kembali ke Beranda
    </a>

    <h1>Register</h1>

    <?php if ($error): ?>

        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <div>

            <label for="name">
                Nama
            </label>

            <input
                type="text"
                id="name"
                name="name"
                autocomplete="name"
                required
            >

        </div>

        <div>

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                required
            >

        </div>

        <div class="password-field">

            <label for="password">
                Password
            </label>

            <div class="password-input-wrapper">

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    aria-label="Tampilkan password"
                    onclick="togglePassword('password', this)"
                >
                    <svg
                        class="eye-icon"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>

            </div>

        </div>

        <div class="password-field">

            <label for="confirm_password">
                Konfirmasi Password
            </label>

            <div class="password-input-wrapper">

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    autocomplete="new-password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    aria-label="Tampilkan password"
                    onclick="togglePassword('confirm_password', this)"
                >
                    <svg
                        class="eye-icon"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>

            </div>

        </div>

        <button type="submit">
            Register
        </button>

    </form>

    <p>
        Sudah punya akun?
        <a href="login.php">Login</a>
    </p>

</main>

<script>
    function togglePassword(inputId, button) {

        const input = document.getElementById(inputId);
        const icon = button.querySelector('.eye-icon');

        if (input.type === 'password') {

            input.type = 'text';

            button.setAttribute(
                'aria-label',
                'Sembunyikan password'
            );

            icon.innerHTML = `
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.6a2 2 0 002.8 2.8"/>
                <path d="M9.9 4.2A10.8 10.8 0 0112 5c6.5 0 10 7 10 7a18.7 18.7 0 01-3.1 3.8"/>
                <path d="M6.6 6.6C3.7 8.6 2 12 2 12s3.5 7 10 7a10.7 10.7 0 004.1-.8"/>
            `;

        } else {

            input.type = 'password';

            button.setAttribute(
                'aria-label',
                'Tampilkan password'
            );

            icon.innerHTML = `
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
            `;
        }
    }
</script>

</body>

</html>