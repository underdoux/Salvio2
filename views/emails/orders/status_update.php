<h2>Order Status Update</h2>

<p>Dear {{customer_name}},</p>

<p>Your order #{{order_number}} has been updated to: <span class="badge badge-{{status_color}}">{{status}}</span></p>

<div class="summary-box">
    <div class="summary-item">
        <span>Order Number:</span>
        <strong>#{{order_number}}</strong>
    </div>
    <div class="summary-item">
        <span>Order Date:</span>
        <span>{{created_at}}</span>
    </div>
    <div class="summary-item">
        <span>Items:</span>
        <span>{{items_count}} items</span>
    </div>
    <div class="summary-item">
        <span>Payment Status:</span>
        <span class="badge badge-{{payment_status_color}}">{{payment_status}}</span>
    </div>
    <div class="summary-item">
        <span>Total Amount:</span>
        <strong>{{total_amount}}</strong>
    </div>
</div>

<p>You can track your order status by clicking the button below:</p>

<a href="{{tracking_url}}" class="button">Track Order</a>

<p>If you have any questions about your order, please don't hesitate to contact our customer service.</p>

<p>Thank you for choosing our service!</p>
