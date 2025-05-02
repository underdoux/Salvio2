<?php
// Get app name from config
$appName = config('app_name', 'POS Pharma');
$appVersion = config('app_version', '1.0.0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($appName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
        .brand-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <main class="form-signin text-center">
        <form action="<?php echo base_url('/public/auth/login'); ?>" method="POST">
            <div class="brand-icon">
                <i class="bi bi-capsule"></i>
            </div>
            <h1 class="h3 mb-3 fw-normal"><?php echo htmlspecialchars($appName); ?></h1>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']); 
                    ?>
                </div>
            <?php endif; ?>

            <div class="form-floating mb-2">
                <input type="text" 
                       class="form-control" 
                       id="username" 
                       name="username" 
                       placeholder="Username" 
                       required 
                       autocomplete="username"
                       autofocus>
                <label for="username">Username</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="Password" 
                       required 
                       autocomplete="current-password">
                <label for="password">Password</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">
                <i class="bi bi-box-arrow-in-right"></i> Sign in
            </button>
            
            <p class="mt-5 mb-3 text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?>
                <br>
                <small>Version <?php echo htmlspecialchars($appVersion); ?></small>
            </p>
        </form>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
