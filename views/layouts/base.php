<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' - ' : ''; ?><?php echo htmlspecialchars(config('app_name')); ?></title>
    
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
<body class="<?php echo $bodyClass ?? ''; ?>">
    <?php if (!isset($hideNav)): ?>
        <?php include __DIR__ . '/nav.php'; ?>
    <?php endif; ?>

    <?php echo $content; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (required for some Bootstrap features) -->
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
