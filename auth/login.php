
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

    <link rel="stylesheet" href="../public/css/style.css">

</head>

<body>

    <main class="auth-container">
        <a href="../index.php" class="back-home">
            ← Kembali ke Beranda
        </a>

        <h1>Login</h1>

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

            <div>

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                >

            </div>

            <div>

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >

            </div>

            <button type="submit">
                Login
            </button>

        </form>

        <p>
            Belum punya akun?
            <a href="register.php">Register</a>
        </p>

    </main>

</body>

</html>

