<h2>Commission Status Update</h2>

<p>Dear {{sales_person}},</p>

<p>Your commission for order #{{order_number}} has been {{status}}.</p>

<div class="summary-box">
    <div class="summary-item">
        <span>Order Number:</span>
        <strong>#{{order_number}}</strong>
    </div>
    <div class="summary-item">
        <span>Commission Amount:</span>
        <strong>{{commission_amount}}</strong>
    </div>
    <div class="summary-item">
        <span>Status:</span>
        <span class="badge badge-{{status_color}}">{{status}}</span>
    </div>
    <?php if ($data['status'] === 'approved'): ?>
    <div class="summary-item">
        <span>Expected Payment Date:</span>
        <span>{{payment_date}}</span>
    </div>
    <?php endif; ?>
    <?php if ($data['status'] === 'rejected'): ?>
    <div class="summary-item">
        <span>Rejection Reason:</span>
        <span>{{rejection_reason}}</span>
    </div>
    <?php endif; ?>
</div>

<?php if ($data['status'] === 'approved'): ?>
<p>Your commission will be processed for payment according to the payment schedule.</p>
<?php endif; ?>

<p>You can view your commission details by clicking the button below:</p>

<a href="{{commission_url}}" class="button">View Commission Details</a>

<p>If you have any questions about your commission, please contact your supervisor or the HR department.</p>

<p>Keep up the great work!</p>
