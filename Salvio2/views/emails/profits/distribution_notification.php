<h2>Profit Distribution Notification</h2>

<p>Dear {{investor_name}},</p>

<p>We are pleased to inform you that the profit distribution for {{period}} has been processed.</p>

<div class="summary-box">
    <h3>Distribution Details</h3>
    <div class="summary-item">
        <span>Period:</span>
        <strong>{{period}}</strong>
    </div>
    <div class="summary-item">
        <span>Total Net Profit:</span>
        <strong>{{total_profit}}</strong>
    </div>
    <div class="summary-item">
        <span>Your Share Percentage:</span>
        <span>{{share_percentage}}%</span>
    </div>
    <div class="summary-item">
        <span>Your Share Amount:</span>
        <strong>{{share_amount}}</strong>
    </div>
</div>

<div class="summary-box">
    <h3>Payment Information</h3>
    <div class="summary-item">
        <span>Payment Method:</span>
        <span>{{payment_method}}</span>
    </div>
    <div class="summary-item">
        <span>Payment Date:</span>
        <strong>{{payment_date}}</strong>
    </div>
    <?php if (!empty($data['reference_number'])): ?>
    <div class="summary-item">
        <span>Reference Number:</span>
        <span>{{reference_number}}</span>
    </div>
    <?php endif; ?>
    <?php if (!empty($data['bank_info'])): ?>
    <div class="summary-item">
        <span>Bank Account:</span>
        <span>{{bank_name}} - {{bank_account}}</span>
    </div>
    <?php endif; ?>
</div>

<div class="summary-box">
    <h3>Period Performance</h3>
    <div class="summary-item">
        <span>Total Revenue:</span>
        <span>{{total_revenue}}</span>
    </div>
    <div class="summary-item">
        <span>Total Costs:</span>
        <span>{{total_costs}}</span>
    </div>
    <div class="summary-item">
        <span>Net Profit:</span>
        <strong>{{net_profit}}</strong>
    </div>
    <div class="summary-item">
        <span>Profit Margin:</span>
        <span class="badge badge-{{margin_color}}">{{profit_margin}}%</span>
    </div>
    <?php if (!empty($data['margin_change'])): ?>
    <div class="summary-item">
        <span>Margin Change:</span>
        <span class="badge badge-{{change_color}}">
            {{margin_change_direction}} {{margin_change}}%
        </span>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($data['insights'])): ?>
<div class="summary-box">
    <h3>Business Insights</h3>
    <div class="summary-item">
        <span>Total Orders:</span>
        <span>{{total_orders}}</span>
    </div>
    <div class="summary-item">
        <span>Average Order Value:</span>
        <span>{{average_order_value}}</span>
    </div>
    <div class="summary-item">
        <span>Top Product Category:</span>
        <span>{{top_category}} ({{top_category_percentage}}%)</span>
    </div>
    <div class="summary-item">
        <span>Customer Growth:</span>
        <span class="badge badge-{{growth_color}}">{{customer_growth}}%</span>
    </div>
</div>
<?php endif; ?>

<p>You can view detailed profit distribution reports by clicking the button below:</p>

<a href="{{report_url}}" class="button">View Detailed Report</a>

<p>If you have any questions about this profit distribution or would like to discuss the business performance, please don't hesitate to contact us.</p>

<p>Thank you for your continued trust and investment in our business.</p>

<?php if (!empty($data['next_distribution'])): ?>
<div class="summary-box">
    <h3>Next Distribution</h3>
    <div class="summary-item">
        <span>Expected Date:</span>
        <span>{{next_distribution_date}}</span>
    </div>
    <div class="summary-item">
        <span>Period:</span>
        <span>{{next_distribution_period}}</span>
    </div>
</div>
<?php endif; ?>
