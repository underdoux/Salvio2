<h2>Commission Payment Notification</h2>

<p>Dear {{sales_person}},</p>

<p>Your commission payment has been processed successfully.</p>

<div class="summary-box">
    <div class="summary-item">
        <span>Payment Date:</span>
        <strong>{{payment_date}}</strong>
    </div>
    <div class="summary-item">
        <span>Payment Amount:</span>
        <strong>{{commission_amount}}</strong>
    </div>
    <div class="summary-item">
        <span>Payment Method:</span>
        <span>{{payment_method}}</span>
    </div>
    <?php if (!empty($data['reference_number'])): ?>
    <div class="summary-item">
        <span>Reference Number:</span>
        <span>{{reference_number}}</span>
    </div>
    <?php endif; ?>
</div>

<div class="summary-box">
    <h3>Commission Details</h3>
    <div class="summary-item">
        <span>Order Number:</span>
        <strong>#{{order_number}}</strong>
    </div>
    <div class="summary-item">
        <span>Order Date:</span>
        <span>{{order_date}}</span>
    </div>
    <div class="summary-item">
        <span>Order Total:</span>
        <span>{{order_total}}</span>
    </div>
    <div class="summary-item">
        <span>Commission Rate:</span>
        <span>{{commission_rate}}%</span>
    </div>
</div>

<?php if (!empty($data['next_commission'])): ?>
<div class="summary-box">
    <h3>Upcoming Commission</h3>
    <div class="summary-item">
        <span>Expected Amount:</span>
        <strong>{{next_commission_amount}}</strong>
    </div>
    <div class="summary-item">
        <span>Expected Date:</span>
        <span>{{next_commission_date}}</span>
    </div>
</div>
<?php endif; ?>

<p>You can view your commission history by clicking the button below:</p>

<a href="{{commission_url}}" class="button">View Commission History</a>

<p>If you have any questions about your commission payment, please contact your supervisor or the HR department.</p>

<p>Thank you for your continued dedication and excellent performance!</p>

<?php if (!empty($data['monthly_summary'])): ?>
<div class="summary-box">
    <h3>Monthly Performance Summary</h3>
    <div class="summary-item">
        <span>Total Orders:</span>
        <span>{{monthly_orders}}</span>
    </div>
    <div class="summary-item">
        <span>Total Sales:</span>
        <span>{{monthly_sales}}</span>
    </div>
    <div class="summary-item">
        <span>Total Commission:</span>
        <strong>{{monthly_commission}}</strong>
    </div>
    <?php if (!empty($data['performance_increase'])): ?>
    <div class="summary-item">
        <span>Performance Increase:</span>
        <span class="badge badge-success">↑ {{performance_increase}}%</span>
    </div>
    <?php endif; ?>
</div>

<p>Keep up the great work! You're making significant contributions to our success.</p>
<?php endif; ?>
