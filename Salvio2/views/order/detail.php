<?php
$pageTitle = 'Order Details';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="order-detail" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>Order #{{ order.order_number }}</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <a href="/orders">Orders</a>
                <span class="separator">/</span>
                <span class="current">Order #{{ order.order_number }}</span>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="action-buttons">
                <button v-if="hasPermission('process_payments') && canProcessPayment"
                        class="btn btn-success"
                        @click="showPaymentModal = true">
                    <i class="fas fa-money-bill"></i> Process Payment
                </button>

                <button v-if="hasPermission('edit_orders') && canEdit"
                        class="btn btn-primary"
                        @click="showEditModal = true">
                    <i class="fas fa-edit"></i> Edit Order
                </button>

                <button class="btn btn-secondary"
                        @click="printOrder">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>

    <!-- Order Status -->
    <div class="status-bar">
        <div class="status-timeline">
            <div v-for="(status, index) in orderStatuses" 
                 :key="status.value"
                 class="status-step"
                 :class="{
                     'completed': isStatusCompleted(status.value),
                     'current': order.status === status.value
                 }">
                <div class="status-icon">
                    <i :class="status.icon"></i>
                </div>
                <div class="status-label">{{ status.label }}</div>
                <div class="status-date" v-if="getStatusDate(status.value)">
                    {{ formatDate(getStatusDate(status.value)) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Order Information -->
    <div class="content-grid">
        <!-- Customer & Order Details -->
        <div class="card">
            <div class="card-header">
                <h2>Order Information</h2>
                <div class="badges">
                    <span class="badge" :class="'badge-' + getStatusColor(order.status)">
                        {{ getStatusLabel(order.status) }}
                    </span>
                    <span class="badge" :class="'badge-' + getPaymentStatusColor(order.payment_status)">
                        {{ getPaymentStatusLabel(order.payment_status) }}
                    </span>
                </div>
            </div>
            
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-section">
                        <h3>Customer Information</h3>
                        <div class="info-group">
                            <label>Name</label>
                            <span>{{ order.customer_name }}</span>
                        </div>
                        <div class="info-group">
                            <label>Phone</label>
                            <span>{{ order.customer_phone }}</span>
                        </div>
                        <div class="info-group" v-if="order.customer_email">
                            <label>Email</label>
                            <span>{{ order.customer_email }}</span>
                        </div>
                        <div class="info-group">
                            <label>Type</label>
                            <span>{{ getCustomerTypeLabel(order.customer_type) }}</span>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3>Order Details</h3>
                        <div class="info-group">
                            <label>Order Date</label>
                            <span>{{ formatDate(order.created_at) }}</span>
                        </div>
                        <div class="info-group">
                            <label>Payment Type</label>
                            <span>{{ getPaymentTypeLabel(order.payment_type) }}</span>
                        </div>
                        <div class="info-group" v-if="order.payment_type === 'installment'">
                            <label>Due Date</label>
                            <span :class="{ 'text-danger': isOverdue }">
                                {{ formatDate(order.due_date) }}
                            </span>
                        </div>
                        <div class="info-group" v-if="order.shipping_address">
                            <label>Shipping Address</label>
                            <span>{{ order.shipping_address }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card">
            <div class="card-header">
                <h2>Order Items</h2>
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
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in order.items" :key="item.id">
                                <td>
                                    {{ item.product_name }}
                                    <small v-if="item.by_order" class="badge badge-info">
                                        By Order
                                    </small>
                                </td>
                                <td>{{ formatCurrency(item.price) }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>{{ formatCurrency(item.price * item.quantity) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right">Subtotal:</td>
                                <td>{{ formatCurrency(order.subtotal) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right">
                                    Tax ({{ order.tax_percentage }}%):
                                </td>
                                <td>{{ formatCurrency(order.tax_amount) }}</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3" class="text-right">
                                    <strong>Total:</strong>
                                </td>
                                <td>
                                    <strong>{{ formatCurrency(order.total) }}</strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h2>Payment History</h2>
                <div class="payment-summary">
                    <div class="summary-item">
                        <label>Total Paid:</label>
                        <span>{{ formatCurrency(totalPaid) }}</span>
                    </div>
                    <div class="summary-item">
                        <label>Remaining:</label>
                        <span>{{ formatCurrency(remainingAmount) }}</span>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <div class="payment-list">
                    <div v-if="!payments.length" class="empty-state">
                        No payments recorded
                    </div>
                    <div v-else v-for="payment in payments" 
                         :key="payment.id" 
                         class="payment-item">
                        <div class="payment-info">
                            <div class="amount">
                                {{ formatCurrency(payment.amount) }}
                            </div>
                            <div class="details">
                                <span class="method">
                                    {{ getPaymentMethodLabel(payment.payment_method) }}
                                </span>
                                <span class="date">
                                    {{ formatDate(payment.payment_date) }}
                                </span>
                            </div>
                        </div>
                        <div class="payment-meta">
                            <div v-if="payment.reference_number" class="reference">
                                Ref: {{ payment.reference_number }}
                            </div>
                            <div v-if="payment.notes" class="notes">
                                {{ payment.notes }}
                            </div>
                            <div class="processed-by">
                                Processed by {{ payment.processed_by }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order History -->
        <div class="card">
            <div class="card-header">
                <h2>Order History</h2>
            </div>
            
            <div class="card-body">
                <div class="timeline">
                    <div v-for="event in orderHistory" 
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <order-form-modal v-if="showEditModal"
                     :order="order"
                     @save="handleOrderUpdate"
                     @close="showEditModal = false" />

    <payment-modal v-if="showPaymentModal"
                  :order="order"
                  @payment-processed="handlePaymentProcessed"
                  @close="showPaymentModal = false" />
</div>

<script>
new Vue({
    el: '#order-detail',
    data: {
        orderId: <?php echo json_encode($orderId); ?>,
        order: {},
        payments: [],
        orderHistory: [],
        showEditModal: false,
        showPaymentModal: false,
        orderStatuses: [
            { value: 'new', label: 'New Order', icon: 'fas fa-shopping-cart' },
            { value: 'processing', label: 'Processing', icon: 'fas fa-cog' },
            { value: 'shipped', label: 'Shipped', icon: 'fas fa-truck' },
            { value: 'completed', label: 'Completed', icon: 'fas fa-check-circle' }
        ],
        loading: true
    },
    computed: {
        totalPaid() {
            return this.payments.reduce((sum, payment) => sum + payment.amount, 0);
        },
        remainingAmount() {
            return this.order.total - this.totalPaid;
        },
        canProcessPayment() {
            return this.remainingAmount > 0 && 
                   !['cancelled', 'refunded'].includes(this.order.status);
        },
        canEdit() {
            return ['new', 'processing'].includes(this.order.status);
        },
        isOverdue() {
            if (!this.order.due_date) return false;
            return new Date(this.order.due_date) < new Date();
        }
    },
    methods: {
        async loadOrder() {
            try {
                const response = await axios.get(`/api/orders/${this.orderId}`);
                this.order = response.data;
            } catch (error) {
                this.$root.showToast('Failed to load order details', 'error');
            }
        },

        async loadPayments() {
            try {
                const response = await axios.get(`/api/orders/${this.orderId}/payments`);
                this.payments = response.data;
            } catch (error) {
                console.error('Failed to load payments:', error);
            }
        },

        async loadOrderHistory() {
            try {
                const response = await axios.get(`/api/orders/${this.orderId}/history`);
                this.orderHistory = response.data;
            } catch (error) {
                console.error('Failed to load order history:', error);
            }
        },

        getStatusColor(status) {
            const colors = {
                new: 'primary',
                processing: 'info',
                shipped: 'warning',
                completed: 'success',
                cancelled: 'danger'
            };
            return colors[status] || 'secondary';
        },

        getPaymentStatusColor(status) {
            const colors = {
                pending: 'warning',
                partial: 'info',
                paid: 'success',
                overdue: 'danger',
                refunded: 'secondary'
            };
            return colors[status] || 'secondary';
        },

        getStatusLabel(status) {
            const labels = {
                new: 'New Order',
                processing: 'Processing',
                shipped: 'Shipped',
                completed: 'Completed',
                cancelled: 'Cancelled'
            };
            return labels[status] || status;
        },

        getPaymentStatusLabel(status) {
            const labels = {
                pending: 'Payment Pending',
                partial: 'Partially Paid',
                paid: 'Paid',
                overdue: 'Overdue',
                refunded: 'Refunded'
            };
            return labels[status] || status;
        },

        getCustomerTypeLabel(type) {
            const labels = {
                pharmacy: 'Pharmacy',
                clinic: 'Clinic',
                hospital: 'Hospital',
                distributor: 'Distributor',
                other: 'Other'
            };
            return labels[type] || type;
        },

        getPaymentTypeLabel(type) {
            const labels = {
                cash: 'Cash',
                transfer: 'Bank Transfer',
                installment: 'Installment'
            };
            return labels[type] || type;
        },

        getPaymentMethodLabel(method) {
            const labels = {
                cash: 'Cash',
                bank_transfer: 'Bank Transfer',
                debit_card: 'Debit Card',
                credit_card: 'Credit Card'
            };
            return labels[method] || method;
        },

        getEventIcon(type) {
            const icons = {
                status_change: 'fas fa-sync',
                payment: 'fas fa-money-bill',
                edit: 'fas fa-edit',
                note: 'fas fa-comment',
                system: 'fas fa-cog'
            };
            return icons[type] || 'fas fa-circle';
        },

        isStatusCompleted(status) {
            const statusIndex = this.orderStatuses.findIndex(s => s.value === status);
            const currentIndex = this.orderStatuses.findIndex(s => s.value === this.order.status);
            return statusIndex < currentIndex;
        },

        getStatusDate(status) {
            const event = this.orderHistory.find(
                e => e.type === 'status_change' && e.data?.new_status === status
            );
            return event?.created_at;
        },

        async handleOrderUpdate() {
            this.showEditModal = false;
            await this.loadOrder();
            await this.loadOrderHistory();
        },

        async handlePaymentProcessed() {
            this.showPaymentModal = false;
            await this.loadOrder();
            await this.loadPayments();
            await this.loadOrderHistory();
        },

        printOrder() {
            window.print();
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
                this.loadOrder(),
                this.loadPayments(),
                this.loadOrderHistory()
            ]);
        } catch (error) {
            console.error('Error initializing page:', error);
        } finally {
            this.loading = false;
        }
    }
});
</script>
