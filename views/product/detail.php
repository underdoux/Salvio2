<?php
$pageTitle = 'Product Details';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="product-detail" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>{{ product.name }}</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <a href="/products">Products</a>
                <span class="separator">/</span>
                <span class="current">{{ product.name }}</span>
            </nav>
        </div>
        
        <div class="header-right">
            <button v-if="hasPermission('edit_products')"
                    class="btn btn-primary"
                    @click="showEditModal = true">
                <i class="fas fa-edit"></i> Edit Product
            </button>
        </div>
    </div>

    <!-- Product Information -->
    <div class="content-grid">
        <!-- Main Info Card -->
        <div class="card">
            <div class="card-header">
                <h2>Product Information</h2>
                <div class="badge" :class="getStockStatusClass">
                    {{ getStockLabel }}
                </div>
            </div>
            
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-group">
                        <label>Category</label>
                        <span>{{ product.category_name }}</span>
                    </div>
                    
                    <div class="info-group">
                        <label>BPOM Registration</label>
                        <span>{{ product.bpom_code || 'Not Registered' }}</span>
                    </div>
                    
                    <div class="info-group">
                        <label>Current Stock</label>
                        <span :class="getStockTextClass">{{ product.stock }}</span>
                    </div>
                    
                    <div class="info-group">
                        <label>Minimum Stock</label>
                        <span>{{ product.min_stock }}</span>
                    </div>
                    
                    <div class="info-group">
                        <label>Selling Price</label>
                        <span>{{ formatCurrency(product.price) }}</span>
                    </div>
                    
                    <div class="info-group">
                        <label>Cost Price</label>
                        <span>{{ formatCurrency(product.cost_price) }}</span>
                    </div>
                    
                    <div class="info-group">
                        <label>Profit Margin</label>
                        <span class="text-success">{{ profitMargin }}%</span>
                    </div>
                    
                    <div class="info-group">
                        <label>Order Type</label>
                        <span>{{ product.by_order ? 'By Order' : 'Stocked' }}</span>
                    </div>
                </div>

                <div class="description" v-if="product.description">
                    <label>Description</label>
                    <p>{{ product.description }}</p>
                </div>
            </div>
        </div>

        <!-- Stock Movement History -->
        <div class="card">
            <div class="card-header">
                <h2>Stock Movement History</h2>
                <button v-if="hasPermission('manage_stock')"
                        class="btn btn-primary"
                        @click="showStockModal = true">
                    <i class="fas fa-plus"></i> Adjust Stock
                </button>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reason</th>
                                <th>Notes</th>
                                <th>Updated By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="6" class="loading">
                                    <div class="spinner"></div> Loading...
                                </td>
                            </tr>
                            <tr v-else-if="!stockMovements.length">
                                <td colspan="6" class="no-data">
                                    No stock movements found
                                </td>
                            </tr>
                            <tr v-else v-for="movement in stockMovements" 
                                :key="movement.id">
                                <td>{{ formatDate(movement.created_at) }}</td>
                                <td>
                                    <span class="badge" 
                                          :class="movement.type === 'in' ? 'badge-success' : 'badge-danger'">
                                        {{ movement.type === 'in' ? 'Stock In' : 'Stock Out' }}
                                    </span>
                                </td>
                                <td>{{ movement.quantity }}</td>
                                <td>{{ movement.reason }}</td>
                                <td>{{ movement.notes }}</td>
                                <td>{{ movement.created_by }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sales History -->
        <div class="card">
            <div class="card-header">
                <h2>Sales History</h2>
                <div class="period-selector">
                    <button v-for="period in periods"
                            :key="period.value"
                            class="btn btn-sm"
                            :class="{ 'btn-primary': selectedPeriod === period.value }"
                            @click="changePeriod(period.value)">
                        {{ period.label }}
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <canvas ref="salesChart"></canvas>
                
                <div class="sales-summary">
                    <div class="summary-item">
                        <label>Total Sales</label>
                        <h3>{{ salesStats.totalQuantity }}</h3>
                    </div>
                    <div class="summary-item">
                        <label>Revenue</label>
                        <h3>{{ formatCurrency(salesStats.totalRevenue) }}</h3>
                    </div>
                    <div class="summary-item">
                        <label>Average Price</label>
                        <h3>{{ formatCurrency(salesStats.averagePrice) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <product-form-modal v-if="showEditModal"
                       :product="product"
                       :categories="categories"
                       @save="updateProduct"
                       @close="showEditModal = false" />

    <stock-adjustment-modal v-if="showStockModal"
                           :product="product"
                           @save="saveStockAdjustment"
                           @close="showStockModal = false" />
</div>

<script>
new Vue({
    el: '#product-detail',
    data: {
        productId: <?php echo json_encode($productId); ?>,
        product: {},
        categories: [],
        stockMovements: [],
        salesStats: {
            totalQuantity: 0,
            totalRevenue: 0,
            averagePrice: 0
        },
        loading: true,
        showEditModal: false,
        showStockModal: false,
        selectedPeriod: '30d',
        periods: [
            { value: '7d', label: '7 Days' },
            { value: '30d', label: '30 Days' },
            { value: '90d', label: '90 Days' },
            { value: '1y', label: '1 Year' }
        ],
        salesChart: null
    },
    computed: {
        getStockStatusClass() {
            if (this.product.stock <= 0) return 'badge-danger';
            if (this.product.stock <= this.product.min_stock) return 'badge-warning';
            return 'badge-success';
        },
        getStockLabel() {
            if (this.product.by_order) return 'By Order';
            if (this.product.stock <= 0) return 'Out of Stock';
            if (this.product.stock <= this.product.min_stock) return 'Low Stock';
            return 'In Stock';
        },
        getStockTextClass() {
            if (this.product.stock <= 0) return 'text-danger';
            if (this.product.stock <= this.product.min_stock) return 'text-warning';
            return 'text-success';
        },
        profitMargin() {
            if (!this.product.price || !this.product.cost_price) return 0;
            return (((this.product.price - this.product.cost_price) / this.product.cost_price) * 100).toFixed(2);
        }
    },
    methods: {
        async loadProduct() {
            try {
                const response = await axios.get(`/api/products/${this.productId}`);
                this.product = response.data;
            } catch (error) {
                this.$root.showToast('Failed to load product details', 'error');
            }
        },

        async loadCategories() {
            try {
                const response = await axios.get('/api/categories');
                this.categories = response.data;
            } catch (error) {
                this.$root.showToast('Failed to load categories', 'error');
            }
        },

        async loadStockMovements() {
            try {
                const response = await axios.get(`/api/products/${this.productId}/stock-movements`);
                this.stockMovements = response.data;
            } catch (error) {
                this.$root.showToast('Failed to load stock movements', 'error');
            }
        },

        async loadSalesData() {
            try {
                const response = await axios.get(`/api/products/${this.productId}/sales`, {
                    params: { period: this.selectedPeriod }
                });
                this.salesStats = response.data.stats;
                this.updateSalesChart(response.data.chart);
            } catch (error) {
                this.$root.showToast('Failed to load sales data', 'error');
            }
        },

        updateSalesChart(data) {
            if (this.salesChart) {
                this.salesChart.destroy();
            }

            const ctx = this.$refs.salesChart.getContext('2d');
            this.salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Sales Quantity',
                            data: data.quantities,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            fill: true
                        },
                        {
                            label: 'Revenue',
                            data: data.revenue,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true,
                            yAxisID: 'revenue'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity'
                            }
                        },
                        revenue: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue (IDR)'
                            }
                        }
                    }
                }
            });
        },

        async updateProduct(productData) {
            try {
                await axios.put(`/api/products/${this.productId}`, productData);
                this.$root.showToast('Product updated successfully', 'success');
                this.showEditModal = false;
                this.loadProduct();
            } catch (error) {
                this.$root.showToast(
                    error.response?.data?.error || 'Failed to update product',
                    'error'
                );
            }
        },

        async saveStockAdjustment(adjustment) {
            try {
                await axios.post(`/api/products/${this.productId}/stock`, adjustment);
                this.$root.showToast('Stock adjusted successfully', 'success');
                this.showStockModal = false;
                this.loadProduct();
                this.loadStockMovements();
            } catch (error) {
                this.$root.showToast('Failed to adjust stock', 'error');
            }
        },

        changePeriod(period) {
            this.selectedPeriod = period;
            this.loadSalesData();
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        },

        formatDate(date) {
            return new Date(date).toLocaleString();
        },

        hasPermission(permission) {
            return this.$root.hasPermission(permission);
        }
    },
    async created() {
        try {
            await Promise.all([
                this.loadProduct(),
                this.loadCategories(),
                this.loadStockMovements()
            ]);
            this.loadSalesData();
        } catch (error) {
            console.error('Error initializing page:', error);
        } finally {
            this.loading = false;
        }
    }
});
</script>
