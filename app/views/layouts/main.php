<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Salvio POS' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= '/Salvio2/public/assets/css/style.css' ?>">
</head>
<body>
    <?php require_once __DIR__ . '/header.php'; ?>

    <main class="container my-4">
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?>">
                <?= $_SESSION['flash']['message'] ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <footer class="bg-light py-3 mt-auto">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> Salvio POS. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/Salvio2/public/assets/js/main.js"></script>
</body>
</html>
