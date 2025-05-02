<?php
// Load bootstrap if not already loaded
if (!function_exists('config')) {
    $app = require_once __DIR__ . '/../../config/bootstrap.php';
    extract($app);
}

// Get app name and version from config
$appName = config('app_name', 'POS Pharma');
$appVersion = config('app_version', '1.0.0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' - ' : ''; ?><?php echo htmlspecialchars($appName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <?php if (isset($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link href="<?php echo asset($style); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($inlineStyles)): ?>
        <style>
            <?php echo $inlineStyles; ?>
        </style>
    <?php endif; ?>
</head>
<body class="<?php echo isset($bodyClass) ? htmlspecialchars($bodyClass) : ''; ?>">
    <?php echo $content ?? ''; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?php echo asset($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($inlineScripts)): ?>
        <script>
            <?php echo $inlineScripts; ?>
        </script>
    <?php endif; ?>
</body>
</html>
