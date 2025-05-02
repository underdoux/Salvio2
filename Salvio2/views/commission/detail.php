<?php
$pageTitle = 'Commission Details';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="commission-detail" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>Commission Details</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <a href="/commissions">Commissions</a>
                <span class="separator">/</span>
                <span class="current">Commission #{{ commission.id }}</span>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="status-badge" :class="getStatusClass(commission.status)">
                {{ getStatusLabel(commission.status) }}
            </div>
        </div>
    </div>

    <!-- Commission Information -->
    <div class="content-grid">
        <!-- Main Info Card -->
        <div class="card">
            <div class="card-header">
                <h2>Commission Information</h2>
                <div class="action-buttons" v-if="canTakeAction">
                    <button v-if="canApprove"
                            class="btn btn-success"
                            @click="showApprovalModal = true">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button v-if="canReject"
                            class="btn btn-danger"
                            @click="showApprovalModal = true">
                        <i class="fas fa-times"></i> Reject
                    </button>
                    <button v-if="canPay"
                            class="btn btn-primary"
                            @click="processPayment">
                        <i class="fas fa-money-bill"></i> Process Payment
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-section">
                        <h3>Basic Information</h3>
                        <div class="info-group">
                            <label>Sales Person</label>
                            <span>{{ commission.user_name }}</span>
                        </div>
                        <div class="info-group">
                            <label>Order Number</label>
                            <a :href="'/orders/' + commission.order_id">
                                {{ commission.order_number }}
                            </a>
                        </div>
                        <div class="info-group">
                            <label>Created Date</label>
                            <span>{{ formatDate(commission.created_at) }}</span>
                        </div>
                        <div class="info-group">
                            <label>Last Updated</label>
                            <span>{{ formatDate(commission.updated_at) }}</span>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3>Commission Details</h3>
                        <div class="info-group">
                            <label>Order Total</label>
                            <span>{{ formatCurrency(commission.order_total) }}</span>
                        </div>
                        <div class="info-group">
                            <label>Commission Rate</label>
                            <span>{{ commission.commission_rate }}%</span>
                        </div>
                        <div class="info-group">
                            <label>Original Amount</label>
                            <span>{{ formatCurrency(commission.original_amount) }}</span>
                        </div>
                        <div class="info-group" v-if="commission.adjustment">
                            <label>Adjustment</label>
                            <span :class="getAdjustmentClass">
                                {{ formatAdjustment(commission.adjustment) }}
                            </span>
                        </div>
                        <div class="info-group highlight">
                            <label>Final Amount</label>
                            <span>{{ formatCurrency(commission.amount) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="commission.adjustment_reason" class="adjustment-reason">
                    <label>Adjustment Reason</label>
                    <p>{{ commission.adjustment_reason }}</p>
                </div>

                <div v-if="commission.notes" class="notes">
                    <label>Notes</label>
                    <p>{{ commission.notes }}</p>
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="card">
            <div class="card-header">
                <h2>Order Details</h2>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in orderItems" :key="item.id">
                                <td>{{ item.product_name }}</td>
                                <td>{{ formatCurrency(item.price) }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>{{ formatCurrency(item.total) }}</td>
                                <td>{{ formatCurrency(item.commission) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right">Subtotal:</td>
                                <td>{{ formatCurrency(orderSummary.subtotal) }}</td>
                                <td>{{ formatCurrency(orderSummary.commission) }}</td>
                            </tr>
                            <tr v-if="orderSummary.adjustments">
                                <td colspan="3" class="text-right">Adjustments:</td>
                                <td>{{ formatCurrency(orderSummary.adjustments) }}</td>
                                <td>{{ formatCurrency(orderSummary.adjustment_commission) }}</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3" class="text-right">
                                    <strong>Total:</strong>
                                </td>
                                <td><strong>{{ formatCurrency(orderSummary.total) }}</strong></td>
                                <td><strong>{{ formatCurrency(orderSummary.total_commission) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Commission History -->
        <div class="card">
            <div class="card-header">
                <h2>Commission History</h2>
            </div>
            
            <div class="card-body">
                <div class="timeline">
                    <div v-for="event in commissionHistory" 
                         :key="event.id" 
                         class="timeline-item">
                        <div class="timeline-icon" :class="event.type">
                            <i :class="getEventIcon(event.type)"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="event-message">{{ event.message }}</div>
                            <div class="event-meta">
                                <span class="date">{{ formatDate(event.created_at) }}</span>
                                <span class="user">{{ event.user_name }}</span>
                            </div>
                            <div v-if="event.notes" class="event-notes">
                                {{ event.notes }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <commission-approval-modal v-if="showApprovalModal"
                            :commission="commission"
                            @approve="handleApproval"
                            @reject="handleRejection"
                            @close="showApprovalModal = false" />
</div>

<script>
new Vue({
    el: '#commission-detail',
    data: {
        commissionId: <?php echo json_encode($commissionId); ?>,
        commission: {},
        orderItems: [],
        commissionHistory: [],
        showApprovalModal: false,
        loading: true
    },
    computed: {
        orderSummary() {
            const subtotal = this.orderItems.reduce((sum, item) => sum + item.total, 0);
            const commission = this.orderItems.reduce((sum, item) => sum + item.commission, 0);
            
            return {
                subtotal,
                commission,
                adjustments: this.commission.adjustment || 0,
                adjustment_commission: this.commission.adjustment_commission || 0,
                total: subtotal + (this.commission.adjustment || 0),
                total_commission: commission + (this.commission.adjustment_commission || 0)
            };
        },
        canTakeAction() {
            return this.canApprove || this.canReject || this.canPay;
        },
        canApprove() {
            return this.hasPermission('approve_commissions') && 
                   this.commission.status === 'pending';
        },
        canReject() {
            return this.hasPermission('approve_commissions') && 
                   this.commission.status === 'pending';
        },
        canPay() {
            return this.hasPermission('process_commission_payments') && 
                   this.commission.status === 'approved';
        },
        getAdjustmentClass() {
            return {
                'text-success': this.commission.adjustment > 0,
                'text-danger': this.commission.adjustment < 0
            };
        }
    },
    methods: {
        async loadCommission() {
            try {
                const response = await axios.get(`/api/commissions/${this.commissionId}`);
                this.commission = response.data;
            } catch (error) {
                this.$root.showToast('Failed to load commission details', 'error');
            }
        },

        async loadOrderItems() {
            try {
                const response = await axios.get(`/api/commissions/${this.commissionId}/items`);
                this.orderItems = response.data;
            } catch (error) {
                console.error('Failed to load order items:', error);
            }
        },

        async loadHistory() {
            try {
                const response = await axios.get(`/api/commissions/${this.commissionId}/history`);
                this.commissionHistory = response.data;
            } catch (error) {
                console.error('Failed to load commission history:', error);
            }
        },

        async handleApproval(data) {
            try {
                await axios.post(`/api/commissions/${this.commissionId}/approve`, data);
                this.$root.showToast('Commission approved successfully', 'success');
                this.showApprovalModal = false;
                this.refreshData();
            } catch (error) {
                this.$root.showToast('Failed to approve commission', 'error');
            }
        },

        async handleRejection(data) {
            try {
                await axios.post(`/api/commissions/${this.commissionId}/reject`, data);
                this.$root.showToast('Commission rejected successfully', 'success');
                this.showApprovalModal = false;
                this.refreshData();
            } catch (error) {
                this.$root.showToast('Failed to reject commission', 'error');
            }
        },

        async processPayment() {
            if (!confirm('Are you sure you want to mark this commission as paid?')) return;

            try {
                await axios.post(`/api/commissions/${this.commissionId}/pay`);
                this.$root.showToast('Commission marked as paid', 'success');
                this.refreshData();
            } catch (error) {
                this.$root.showToast('Failed to process payment', 'error');
            }
        },

        refreshData() {
            this.loadCommission();
            this.loadHistory();
        },

        getStatusClass(status) {
            const classes = {
                pending: 'badge-warning',
                approved: 'badge-info',
                paid: 'badge-success',
                rejected: 'badge-danger'
            };
            return classes[status] || 'badge-secondary';
        },

        getStatusLabel(status) {
            const labels = {
                pending: 'Pending',
                approved: 'Approved',
                paid: 'Paid',
                rejected: 'Rejected'
            };
            return labels[status] || status;
        },

        getEventIcon(type) {
            const icons = {
                created: 'fas fa-plus',
                approved: 'fas fa-check',
                rejected: 'fas fa-times',
                paid: 'fas fa-money-bill',
                adjusted: 'fas fa-sliders-h'
            };
            return icons[type] || 'fas fa-circle';
        },

        formatAdjustment(amount) {
            return `${amount > 0 ? '+' : ''}${this.formatCurrency(amount)}`;
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        },

        formatDate(date) {
            return new Date(date).toLocaleString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        hasPermission(permission) {
            return this.$root.hasPermission(permission);
        }
    },
    async created() {
        try {
            await Promise.all([
                this.loadCommission(),
                this.loadOrderItems(),
                this.loadHistory()
            ]);
        } catch (error) {
            console.error('Error initializing page:', error);
        } finally {
            this.loading = false;
        }
    }
});
</script>
