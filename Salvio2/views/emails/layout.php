<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Pharma Notification</title>
    <style>
        /* Reset styles */
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
        }

        /* Container */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #007bff;
        }

        .header img {
            max-height: 50px;
        }

        /* Content */
        .content {
            padding: 30px 20px;
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-primary { background-color: #e3f2fd; color: #1976d2; }
        .badge-success { background-color: #e8f5e9; color: #2e7d32; }
        .badge-warning { background-color: #fff3e0; color: #f57c00; }
        .badge-danger { background-color: #ffebee; color: #c62828; }

        /* Buttons */
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            margin: 20px 0;
        }

        .button:hover {
            background-color: #0056b3;
        }

        /* Summary Box */
        .summary-box {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-item:last-child {
            margin-bottom: 0;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: #666666;
            font-size: 12px;
            border-top: 1px solid #e0e0e0;
        }

        .footer p {
            margin: 5px 0;
        }

        .social-links {
            margin: 15px 0;
        }

        .social-links a {
            color: #666666;
            text-decoration: none;
            margin: 0 10px;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }

            .content {
                padding: 20px 15px;
            }

            .data-table {
                font-size: 14px;
            }

            .data-table th,
            .data-table td {
                padding: 8px;
            }

            .button {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="<?= $_ENV['APP_URL'] ?>/assets/images/logo.png" alt="POS Pharma">
        </div>

        <div class="content">
            <?= $body ?>
        </div>

        <div class="footer">
            <div class="social-links">
                <?php if (!empty($_ENV['SOCIAL_FACEBOOK'])): ?>
                    <a href="<?= $_ENV['SOCIAL_FACEBOOK'] ?>" target="_blank">Facebook</a>
                <?php endif; ?>

                <?php if (!empty($_ENV['SOCIAL_TWITTER'])): ?>
                    <a href="<?= $_ENV['SOCIAL_TWITTER'] ?>" target="_blank">Twitter</a>
                <?php endif; ?>

                <?php if (!empty($_ENV['SOCIAL_INSTAGRAM'])): ?>
                    <a href="<?= $_ENV['SOCIAL_INSTAGRAM'] ?>" target="_blank">Instagram</a>
                <?php endif; ?>
            </div>

            <p>
                <?= $_ENV['COMPANY_NAME'] ?><br>
                <?= $_ENV['COMPANY_ADDRESS'] ?>
            </p>

            <p>
                If you have any questions, please contact us:<br>
                Email: <?= $_ENV['SUPPORT_EMAIL'] ?><br>
                Phone: <?= $_ENV['SUPPORT_PHONE'] ?>
            </p>

            <p>
                This email was sent to you because you are registered with <?= $_ENV['APP_NAME'] ?>.<br>
                To unsubscribe from these notifications, please update your notification preferences in your account settings.
            </p>
        </div>
    </div>
</body>
</html>
