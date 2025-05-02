<h2>Payment Confirmation</h2>

<p>Dear {{customer_name}},</p>

<p>We have received your payment for order #{{order_number}}. Thank you for your payment!</p>

<div class="summary-box">
    <div class="summary-item">
        <span>Order Number:</span>
        <strong>#{{order_number}}</strong>
    </div>
    <div class="summary-item">
        <span>Payment Date:</span>
        <span>{{payment_date}}</span>
    </div>
    <div class="summary-item">
        <span>Payment Method:</span>
        <span>{{payment_method}}</span>
    </div>
    <div class="summary-item">
        <span>Amount Paid:</span>
        <strong>{{amount_paid}}</strong>
    </div>
    <div class="summary-item">
        <span>Remaining Balance:</span>
        <strong>{{remaining_balance}}</strong>
    </div>
    <div class="summary-item">
        <span>Payment Status:</span>
        <span class="badge badge-{{payment_status_color}}">{{payment_status}}</span>
    </div>
</div>

<?php if ($data['payment_status'] === 'partial'): ?>
<div class="summary-box">
    <h3>Next Payment Information</h3>
    <div class="summary-item">
        <span>Due Date:</span>
        <strong>{{next_payment_date}}</strong>
    </div>
    <div class="summary-item">
        <span>Amount Due:</span>
        <strong>{{next_payment_amount}}</strong>
    </div>
</div>

<p>Please ensure to make the next payment before the due date to avoid any delays in your order processing.</p>
<?php endif; ?>

<p>You can view your order details by clicking the button below:</p>

<a href="{{tracking_url}}" class="button">View Order Details</a>

<p>If you have any questions about your payment, please don't hesitate to contact our customer service.</p>

<p>Thank you for your business!</p>
