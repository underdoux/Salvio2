<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'POS & Pharmaceutical Distribution System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <style>
        .debug-panel {
            position: fixed;
            bottom: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            max-width: 400px;
            max-height: 300px;
            overflow: auto;
            z-index: 9999;
        }
        .debug-panel pre {
            margin: 0;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?= $baseUrl ?>/">POS System</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $baseUrl ?>/orders">Orders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $baseUrl ?>/products">Products</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $baseUrl ?>/commissions">Commissions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $baseUrl ?>/profit-sharing">Profit Sharing</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $baseUrl ?>/analytics">Analytics</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $baseUrl ?>/logout">Logout</a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="container py-4">
        <?php if (isset($title)): ?>
        <h1 class="mb-4"><?= htmlspecialchars($title) ?></h1>
        <?php endif; ?>

        <?php if (isset($description)): ?>
        <p class="description mb-4"><?= htmlspecialchars($description) ?></p>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?= date('Y') ?> POS & Pharmaceutical Distribution System</p>
        </div>
    </footer>

    <?php if (isset($debugInfo)): ?>
    <div class="debug-panel">
        <h4>Debug Information</h4>
        <pre><?= json_encode($debugInfo, JSON_PRETTY_PRINT) ?></pre>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/main.js"></script>
</body>
</html>
