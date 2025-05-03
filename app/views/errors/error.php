<?php
// General error page template
$baseUrl = isset($baseUrl) ? $baseUrl : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Salvio POS</title>
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
            margin-top: 20px;
        }
        .back-link:hover {
            background-color: #0056b3;
        }
        .debug-panel {
            margin-top: 30px;
            text-align: left;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .debug-panel pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            background: #fff;
            padding: 15px;
            border-radius: 3px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">Error</div>
        <h1 class="error-message">An Error Occurred</h1>
        <p>We apologize for the inconvenience. Please try again later.</p>
        <a href="<?= $baseUrl ?>/" class="back-link">Back to Home</a>

        <?php if (isset($debugInfo) && defined('DEBUG_MODE') && DEBUG_MODE): ?>
        <div class="debug-panel">
            <h4>Debug Information</h4>
            <pre><?= json_encode($debugInfo, JSON_PRETTY_PRINT) ?></pre>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
