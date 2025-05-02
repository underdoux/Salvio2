<?php
// If no title is set, use app name
$title = isset($title) ? $title . ' - ' . config('app_name') : config('app_name');
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo asset('css/style.css'); ?>" rel="stylesheet">
    
    <?php if (isset($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
            <link href="<?php echo asset($style); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($inlineStyles)): ?>
        <style><?php echo $inlineStyles; ?></style>
    <?php endif; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <?php if (!isset($hideNav)): ?>
        <?php include __DIR__ . '/nav.php'; ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash']['type'] ?? 'info'); ?> alert-dismissible fade show">
                <?php 
                    echo htmlspecialchars($_SESSION['flash']['message'] ?? ''); 
                    unset($_SESSION['flash']); 
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <main class="py-4">
        <?php echo $content; ?>
    </main>

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(config('app_name')); ?>
                <br>
                <small>Version <?php echo htmlspecialchars(config('app_version')); ?></small>
            </span>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo asset('js/app.js'); ?>"></script>

    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo asset($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($inlineScripts)): ?>
        <script><?php echo $inlineScripts; ?></script>
    <?php endif; ?>
</body>
</html>
