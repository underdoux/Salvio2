<?php
$pageTitle = 'Products';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="products-page" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>Products</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Products</span>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="view-options">
                <button class="btn btn-icon" 
                        :class="{ active: viewMode === 'list' }"
                        @click="viewMode = 'list'"
                        title="List View">
                    <i class="fas fa-list"></i>
                </button>
                <button class="btn btn-icon"
                        :class="{ active: viewMode === 'grid' }"
                        @click="viewMode = 'grid'"
                        title="Grid View">
                    <i class="fas fa-grid"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-content">
                <h3>{{ stats.totalProducts }}</h3>
                <p>Total Products</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3>{{ stats.lowStock }}</h3>
                <p>Low Stock Items</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-content">
                <h3>{{ stats.byOrder }}</h3>
                <p>By Order Items</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-content">
                <h3>{{ stats.categories }}</h3>
                <p>Categories</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-card">
        <!-- List View -->
        <product-list v-if="viewMode === 'list'"
                     ref="productList"
                     @refresh="loadStats" />

        <!-- Grid View -->
        <div v-else class="product-grid">
            <div v-for="product in products" 
                 :key="product.id" 
                 class="product-card">
                <div class="product-header">
                    <span class="badge" 
                          :class="getStockStatusClass(product)">
                        {{ getStockLabel(product) }}
                    </span>
                    <div class="product-actions">
                        <button v-if="hasPermission('edit_products')"
                                class="btn btn-icon btn-sm"
                                @click="editProduct(product)"
                                title="Edit Product">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button v-if="hasPermission('manage_stock')"
                                class="btn btn-icon btn-sm"
                                @click="adjustStock(product)"
                                title="Adjust Stock">
                            <i class="fas fa-box"></i>
                        </button>
                    </div>
                </div>

                <div class="product-body">
                    <h3>{{ product.name }}</h3>
                    <div class="product-category">
                        {{ product.category_name }}
                    </div>
                    <div class="product-details">
                        <div class="detail-item">
                            <label>Stock:</label>
                            <span>{{ product.stock }}</span>
                        </div>
                        <div class="detail-item">
                            <label>Price:</label>
                            <span>{{ formatCurrency(product.price) }}</span>
                        </div>
                        <div class="detail-item" v-if="product.bpom_code">
                            <label>BPOM:</label>
                            <span>{{ product.bpom_code }}</span>
                        </div>
                    </div>
                </div>

                <div class="product-footer">
                    <button class="btn btn-primary btn-sm btn-block"
                            @click="viewProduct(product)">
                        View Details
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <product-form-modal v-if="showProductModal"
                       :product="selectedProduct"
                       :categories="categories"
                       @save="saveProduct"
                       @close="closeProductModal" />

    <stock-adjustment-modal v-if="showStockModal"
                           :product="selectedProduct"
                           @save="saveStockAdjustment"
                           @close="showStockModal = false" />

    <import-modal v-if="showImportModal"
                 @import="handleImport"
                 @close="showImportModal = false" />
</div>

<script>
new Vue({
    el: '#products-page',
    data: {
        viewMode: 'list',
        products: [],
        categories: [],
        stats: {
            totalProducts: 0,
            lowStock: 0,
            byOrder: 0,
            categories: 0
        },
        selectedProduct: null,
        showProductModal: false,
        showStockModal: false,
        showImportModal: false,
        loading: false
    },
    methods: {
        async loadStats() {
            try {
                const response = await axios.get('/api/products/stats');
                this.stats = response.data;
            } catch (error) {
                this.$root.showToast('Failed to load statistics', 'error');
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

        editProduct(product) {
            this.selectedProduct = { ...product };
            this.showProductModal = true;
        },

        adjustStock(product) {
            this.selectedProduct = product;
            this.showStockModal = true;
        },

        viewProduct(product) {
            window.location.href = `/products/${product.id}`;
        },

        async saveProduct(productData) {
            try {
                if (productData.id) {
                    await axios.put(`/api/products/${productData.id}`, productData);
                    this.$root.showToast('Product updated successfully', 'success');
                } else {
                    await axios.post('/api/products', productData);
                    this.$root.showToast('Product created successfully', 'success');
                }
                this.closeProductModal();
                this.refreshData();
            } catch (error) {
                this.$root.showToast(
                    error.response?.data?.error || 'Failed to save product', 
                    'error'
                );
            }
        },

        async saveStockAdjustment(adjustment) {
            try {
                await axios.post(
                    `/api/products/${this.selectedProduct.id}/stock`,
                    adjustment
                );
                this.$root.showToast('Stock adjusted successfully', 'success');
                this.showStockModal = false;
                this.refreshData();
            } catch (error) {
                this.$root.showToast('Failed to adjust stock', 'error');
            }
        },

        closeProductModal() {
            this.showProductModal = false;
            this.selectedProduct = null;
        },

        refreshData() {
            if (this.viewMode === 'list') {
                this.$refs.productList.loadProducts();
            } else {
                this.loadProducts();
            }
            this.loadStats();
        },

        getStockStatusClass(product) {
            if (product.stock <= 0) return 'badge-danger';
            if (product.stock <= product.min_stock) return 'badge-warning';
            return 'badge-success';
        },

        getStockLabel(product) {
            if (product.by_order) return 'By Order';
            if (product.stock <= 0) return 'Out of Stock';
            if (product.stock <= product.min_stock) return 'Low Stock';
            return 'In Stock';
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        },

        hasPermission(permission) {
            return this.$root.hasPermission(permission);
        }
    },
    created() {
        this.loadStats();
        this.loadCategories();
    }
});
</script>
