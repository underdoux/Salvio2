<?php

return [
    // Order Status Templates
    'order_new' => [
        'email' => [
            'subject' => 'Order Confirmation - #{{order_number}}',
            'body' => file_get_contents(__DIR__ . '/../views/emails/orders/status_update.php')
        ],
        'whatsapp' => [
            'message' => "🛍️ *Order Confirmation #{{order_number}}*\n\n" .
                        "Hi {{customer_name}},\n" .
                        "Your order has been received and is being processed.\n\n" .
                        "📦 Order Details:\n" .
                        "- Items: {{items_count}}\n" .
                        "- Total: {{total_amount}}\n\n" .
                        "Track your order here: {{tracking_url}}\n\n" .
                        "Need help? Contact us at {{support_phone}}"
        ]
    ],
    'order_processing' => [
        'email' => [
            'subject' => 'Your Order #{{order_number}} is Being Processed',
            'body' => file_get_contents(__DIR__ . '/../views/emails/orders/status_update.php')
        ],
        'whatsapp' => [
            'message' => "🔄 *Order Update #{{order_number}}*\n\n" .
                        "Hi {{customer_name}},\n" .
                        "Your order is now being processed.\n\n" .
                        "We'll notify you once it's ready for shipping.\n\n" .
                        "Track your order: {{tracking_url}}"
        ]
    ],
    'order_shipped' => [
        'email' => [
            'subject' => 'Your Order #{{order_number}} has been Shipped',
            'body' => file_get_contents(__DIR__ . '/../views/emails/orders/status_update.php')
        ],
        'whatsapp' => [
            'message' => "🚚 *Order Shipped #{{order_number}}*\n\n" .
                        "Hi {{customer_name}},\n" .
                        "Your order is on its way!\n\n" .
                        "Track your delivery: {{tracking_url}}\n\n" .
                        "Expected delivery: {{delivery_date}}"
        ]
    ],
    'order_payment' => [
        'email' => [
            'subject' => 'Payment Confirmation - Order #{{order_number}}',
            'body' => file_get_contents(__DIR__ . '/../views/emails/orders/payment_confirmation.php')
        ],
        'whatsapp' => [
            'message' => "💰 *Payment Confirmation #{{order_number}}*\n\n" .
                        "Hi {{customer_name}},\n" .
                        "We've received your payment of {{amount_paid}}.\n\n" .
                        "Payment Details:\n" .
                        "- Method: {{payment_method}}\n" .
                        "- Date: {{payment_date}}\n" .
                        "- Status: {{payment_status}}\n" .
                        "{{remaining_message}}\n\n" .
                        "View details: {{tracking_url}}"
        ]
    ],

    // Commission Templates
    'commission_approved' => [
        'email' => [
            'subject' => 'Commission Approved - Order #{{order_number}}',
            'body' => file_get_contents(__DIR__ . '/../views/emails/commissions/status_update.php')
        ],
        'whatsapp' => [
            'message' => "✅ *Commission Approved*\n\n" .
                        "Hi {{sales_person}},\n" .
                        "Your commission for Order #{{order_number}} has been approved.\n\n" .
                        "Details:\n" .
                        "- Amount: {{commission_amount}}\n" .
                        "- Payment Date: {{payment_date}}\n\n" .
                        "View details: {{commission_url}}"
        ]
    ],
    'commission_payment' => [
        'email' => [
            'subject' => 'Commission Payment Processed',
            'body' => file_get_contents(__DIR__ . '/../views/emails/commissions/payment_notification.php')
        ],
        'whatsapp' => [
            'message' => "💸 *Commission Payment*\n\n" .
                        "Hi {{sales_person}},\n" .
                        "Your commission has been paid.\n\n" .
                        "Payment Details:\n" .
                        "- Amount: {{commission_amount}}\n" .
                        "- Date: {{payment_date}}\n" .
                        "- Method: {{payment_method}}\n\n" .
                        "Monthly Summary:\n" .
                        "- Orders: {{monthly_orders}}\n" .
                        "- Total Commission: {{monthly_commission}}\n\n" .
                        "Keep up the great work! 🌟"
        ]
    ],

    // Profit Distribution Templates
    'profit_calculated' => [
        'email' => [
            'subject' => 'Profit Distribution Calculated - {{period}}',
            'body' => file_get_contents(__DIR__ . '/../views/emails/profits/distribution_notification.php')
        ],
        'whatsapp' => [
            'message' => "📊 *Profit Distribution Calculated*\n\n" .
                        "Hi {{investor_name}},\n" .
                        "The profit distribution for {{period}} has been calculated.\n\n" .
                        "Details:\n" .
                        "- Net Profit: {{net_profit}}\n" .
                        "- Your Share: {{share_amount}} ({{share_percentage}}%)\n" .
                        "- Payment Date: {{payment_date}}\n\n" .
                        "View full report: {{report_url}}"
        ]
    ],
    'profit_distributed' => [
        'email' => [
            'subject' => 'Profit Distribution Payment - {{period}}',
            'body' => file_get_contents(__DIR__ . '/../views/emails/profits/distribution_notification.php')
        ],
        'whatsapp' => [
            'message' => "💰 *Profit Distribution Payment*\n\n" .
                        "Hi {{investor_name}},\n" .
                        "Your profit share for {{period}} has been paid.\n\n" .
                        "Payment Details:\n" .
                        "- Amount: {{share_amount}}\n" .
                        "- Method: {{payment_method}}\n" .
                        "- Date: {{payment_date}}\n\n" .
                        "Period Performance:\n" .
                        "- Revenue: {{total_revenue}}\n" .
                        "- Net Profit: {{net_profit}}\n" .
                        "- Margin: {{profit_margin}}%\n\n" .
                        "View detailed report: {{report_url}}"
        ]
    ],

    // Admin Notifications
    'admin_low_stock' => [
        'email' => [
            'subject' => '⚠️ Low Stock Alert',
            'body' => 'Product {{product_name}} is running low on stock ({{current_stock}} remaining).'
        ],
        'whatsapp' => [
            'message' => "⚠️ *Low Stock Alert*\n\n" .
                        "Product: {{product_name}}\n" .
                        "Current Stock: {{current_stock}}\n" .
                        "Minimum Stock: {{min_stock}}\n\n" .
                        "Please review and restock if necessary."
        ]
    ],
    'admin_payment_overdue' => [
        'email' => [
            'subject' => '🚨 Payment Overdue Alert',
            'body' => 'Payment for Order #{{order_number}} is overdue ({{days_overdue}} days).'
        ],
        'whatsapp' => [
            'message' => "🚨 *Payment Overdue*\n\n" .
                        "Order: #{{order_number}}\n" .
                        "Customer: {{customer_name}}\n" .
                        "Amount: {{amount_due}}\n" .
                        "Days Overdue: {{days_overdue}}\n\n" .
                        "Please follow up with the customer."
        ]
    ]
];
