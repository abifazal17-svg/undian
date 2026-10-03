<?php
require_once __DIR__ . '/../config.php';

$error = '';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    $dbHash = getSetting('admin_password');

    // Check credentials (user is DEFAULT_ADMIN_USER, pass checked via hash or fallback)
    if ($username === DEFAULT_ADMIN_USER && (password_verify($password, $dbHash) || $password === DEFAULT_ADMIN_PASS)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password admin salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Portal Undian ShopeeFood</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">

    <div class="form-card" style="width: 100%; max-width: 420px; padding: 2.5rem 2rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 56px; height: 56px; font-size: 1.75rem;">🔐</div>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: #fff;">Login Admin</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.3rem;">Masuk ke dashboard pengelola undian</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 0.8rem 1rem; border-radius: 12px; font-size: 0.9rem; margin-bottom: 1.5rem; text-align: center;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="username">Username Admin</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" required value="admin">
                    <span class="input-icon">👤</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required>
                    <span class="input-icon">🔑</span>
                </div>
                <div class="form-hint">Password bawaan: <code>admin123</code></div>
            </div>

            <button type="submit" class="btn-submit" style="margin-top: 1rem;">
                🚀 Masuk Dashboard
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="../index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem;">← Kembali ke Halaman Utama Client</a>
        </div>
    </div>

</body>
</html>
