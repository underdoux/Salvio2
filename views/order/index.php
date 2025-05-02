<?php
$pageTitle = 'Orders';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="orders-page" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>Orders</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Orders</span>
            </nav>
        </div>
    </div>

    <!-- Order List Component -->
    <order-list ref="orderList" />

    <!-- Modals -->
    <order-form-modal v-if="showOrderModal"
                     :order="selectedOrder"
                     @save="handleOrderSave"
                     @close="closeOrderModal" />

    <payment-modal v-if="showPaymentModal"
                  :order="selectedOrder"
                  @payment-processed="handlePaymentProcessed"
                  @close="closePaymentModal" />
</div>

<script>
new Vue({
    el: '#orders-page',
    data: {
        showOrderModal: false,
        showPaymentModal: false,
        selectedOrder: null
    },
    methods: {
        openOrderModal(order = null) {
            this.selectedOrder = order;
            this.showOrderModal = true;
        },

        closeOrderModal() {
            this.showOrderModal = false;
            this.selectedOrder = null;
        },

        openPaymentModal(order) {
            this.selectedOrder = order;
            this.showPaymentModal = true;
        },

        closePaymentModal() {
            this.showPaymentModal = false;
            this.selectedOrder = null;
        },

        handleOrderSave() {
            this.closeOrderModal();
            this.$refs.orderList.loadOrders();
        },

        handlePaymentProcessed({ amount, status }) {
            this.closePaymentModal();
            this.$refs.orderList.loadOrders();
        }
    }
});
</script>
