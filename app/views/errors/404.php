<?php
// Ensure no layout is loaded for error page
$this->layout = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - Salvio POS</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <style>
        .error-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .error-code {
            font-size: 72px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 24px;
            color: #343a40;
            margin-bottom: 30px;
        }
        .back-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-message">Page Not Found</h1>
        <p>The page you are looking for could not be found.</p>
        <a href="<?= $baseUrl ?>/" class="back-link">Back to Home</a>
    </div>

    <?php if (isset($debugInfo)): ?>
    <div class="debug-panel">
        <h4>Debug Information</h4>
        <pre><?= json_encode($debugInfo, JSON_PRETTY_PRINT) ?></pre>
    </div>
    <?php endif; ?>
</body>
</html>
