<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Email dan password wajib diisi.';

    } else {

        $stmt = $pdo->prepare(
            'SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {

                header('Location: ../admin/dashboard.php');

            } else {

                header('Location: ../index.php');

            }

            exit;

        } else {

            $error = 'Email atau password salah.';
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

    <title>Login - Hotel Reservation</title>

    <link
        rel="stylesheet"
        href="../public/css/style.css"
    >

</head>

<body>

    <main class="auth-container">

        <a
            href="../index.php"
            class="back-home"
        >
            ← Kembali ke Beranda
        </a>


        <h1>
            Login
        </h1>


        <?php if ($success): ?>

            <p class="success">

                <?= htmlspecialchars($success) ?>

            </p>

        <?php endif; ?>


        <?php if ($error): ?>

            <p class="error">

                <?= htmlspecialchars($error) ?>

            </p>

        <?php endif; ?>


        <form method="POST">

            <!-- =========================
                 EMAIL
            ========================== -->

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


            <!-- =========================
                 PASSWORD
            ========================== -->

            <div class="password-field">

                <label for="password">
                    Password
                </label>


                <div class="password-input-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
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

                            <path
                                d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"
                            />

                            <circle
                                cx="12"
                                cy="12"
                                r="3"
                            />

                        </svg>

                    </button>

                </div>

            </div>


            <!-- =========================
                 LOGIN BUTTON
            ========================== -->

            <button type="submit">

                Login

            </button>

        </form>


        <p>

            Belum punya akun?

            <a href="register.php">
                Register
            </a>

        </p>

    </main>


    <!-- =========================
         TOGGLE PASSWORD
    ========================== -->

    <script>

        function togglePassword(inputId, button) {

            const input =
                document.getElementById(inputId);

            const icon =
                button.querySelector('.eye-icon');


            if (input.type === 'password') {

                input.type = 'text';

                button.setAttribute(
                    'aria-label',
                    'Sembunyikan password'
                );


                icon.innerHTML = `

                    <path d="M3 3l18 18"/>

                    <path
                        d="M10.6 10.6a2 2 0 002.8 2.8"
                    />

                    <path
                        d="M9.9 4.2A10.8 10.8 0 0112 5c6.5 0 10 7 10 7a18.7 18.7 0 01-3.1 3.8"
                    />

                    <path
                        d="M6.6 6.6C3.7 8.6 2 12 2 12s3.5 7 10 7a10.7 10.7 0 004.1-.8"
                    />

                `;

            } else {

                input.type = 'password';

                button.setAttribute(
                    'aria-label',
                    'Tampilkan password'
                );


                icon.innerHTML = `

                    <path
                        d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"
                    />

                    <circle
                        cx="12"
                        cy="12"
                        r="3"
                    />

                `;

            }

        }

    </script>

</body>

</html>