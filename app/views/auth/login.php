<?php
// Ensure no layout is loaded for login page
$this->layout = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Salvio POS</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h1>Login - Salvio POS</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= $baseUrl ?>/login" class="login-form">
            <div class="form-group">
                <label for="login-username">Username</label>
                <input type="text" 
                       id="login-username" 
                       name="username" 
                       required 
                       autocomplete="username"
                       value="<?= htmlspecialchars($username ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="login-password">Password</label>
                <input type="password" 
                       id="login-password" 
                       name="password" 
                       required 
                       autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> POS & Pharmaceutical Distribution System</p>
    </footer>

    <?php if (isset($debugInfo)): ?>
    <div class="debug-panel">
        <h4>Debug Information</h4>
        <pre><?= json_encode($debugInfo, JSON_PRETTY_PRINT) ?></pre>
    </div>
    <?php endif; ?>
</body>
</html>
