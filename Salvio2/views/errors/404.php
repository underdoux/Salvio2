<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .error-page {
            width: 100%;
            max-width: 600px;
            padding: 15px;
            margin: auto;
            text-align: center;
        }
        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <main class="error-page">
        <div class="error-icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h1 class="display-1">404</h1>
        <h2 class="h3 mb-3">Page Not Found</h2>
        <p class="text-muted mb-4">
            The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>
        <div class="d-grid gap-2 col-6 mx-auto">
            <a href="<?php echo htmlspecialchars(base_url('/')); ?>" class="btn btn-primary">
                <i class="bi bi-house-door"></i> Go to Home
            </a>
            <button onclick="history.back()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Go Back
            </button>
        </div>
        <p class="mt-5 mb-3 text-muted">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($config['app_name']); ?>
        </p>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
