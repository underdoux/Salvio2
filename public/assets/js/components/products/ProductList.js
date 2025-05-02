Vue.component('product-list', {
    data() {
        return {
            products: [],
            loading: false,
            pagination: {
                currentPage: 1,
                totalPages: 1,
                perPage: 10,
                total: 0
            },
            filters: {
                search: '',
                category: '',
                stock_status: '',
                by_order: ''
            },
            sort: {
                key: 'created_at',
                direction: 'desc'
            },
            selectedProducts: [],
            categories: [],
            showAddModal: false,
            showEditModal: false,
            currentProduct: null,
            columns: [
                { key: 'name', label: 'Product Name' },
                { key: 'category_name', label: 'Category' },
                { key: 'stock', label: 'Stock', format: 'number' },
                { key: 'price', label: 'Price', format: 'currency' },
                { key: 'by_order', label: 'By Order' },
                { key: 'created_at', label: 'Created At', format: 'datetime' }
            ]
        };
    },
    template: `
        <div class="product-list">
            <!-- Action Bar -->
            <div class="action-bar">
                <div class="action-bar-left">
                    <button v-if="hasPermission('create_products')" 
                            class="btn btn-primary" 
                            @click="showAddModal = true">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                    
                    <button v-if="selectedProducts.length && hasPermission('delete_products')" 
                            class="btn btn-danger" 
                            @click="confirmDeleteSelected">
                        Delete Selected
                    </button>
                </div>

                <div class="action-bar-right">
                    <button class="btn btn-secondary" @click="exportProducts">
                        <i class="fas fa-download"></i> Export
                    </button>
                    
                    <button v-if="hasPermission('import_products')" 
                            class="btn btn-secondary" 
                            @click="showImportModal = true">
                        <i class="fas fa-upload"></i> Import
                    </button>
                </div>
            </div>

            <!-- Data Table -->
            <data-table
                :columns="columns"
                :data="products"
                :loading="loading"
                :pagination="pagination"
                @sort="handleSort"
                @page-change="handlePageChange"
                @selection-change="handleSelectionChange">
                
                <!-- Custom Filters -->
                <template #filters>
                    <div class="filter-group">
                        <select v-model="filters.category" @change="loadProducts">
                            <option value="">All Categories</option>
                            <option v-for="cat in categories" 
                                    :key="cat.id" 
                                    :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select v-model="filters.stock_status" @change="loadProducts">
                            <option value="">All Stock Status</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select v-model="filters.by_order" @change="loadProducts">
                            <option value="">All Types</option>
                            <option value="1">By Order</option>
                            <option value="0">Stocked</option>
                        </select>
                    </div>
                </template>

                <!-- Custom Cell Renderers -->
                <template #cell-by_order="{ item }">
                    <span class="badge" :class="item.by_order ? 'badge-info' : 'badge-success'">
                        {{ item.by_order ? 'By Order' : 'Stocked' }}
                    </span>
                </template>

                <template #cell-stock="{ item }">
                    <span class="badge" 
                          :class="getStockStatusClass(item.stock, item.min_stock)">
                        {{ item.stock }}
                    </span>
                </template>

                <!-- Actions Column -->
                <template #actions="{ item }">
                    <div class="action-buttons">
                        <button class="btn btn-icon btn-info" 
                                @click="viewProduct(item)"
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        
                        <button v-if="hasPermission('edit_products')"
                                class="btn btn-icon btn-primary" 
                                @click="editProduct(item)"
                                title="Edit Product">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <button v-if="hasPermission('manage_stock')"
                                class="btn btn-icon btn-secondary" 
                                @click="adjustStock(item)"
                                title="Adjust Stock">
                            <i class="fas fa-box"></i>
                        </button>
                        
                        <button v-if="hasPermission('delete_products')"
                                class="btn btn-icon btn-danger" 
                                @click="deleteProduct(item)"
                                title="Delete Product">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>
            </data-table>

            <!-- Add/Edit Product Modal -->
            <product-form-modal
                v-if="showAddModal || showEditModal"
                :product="currentProduct"
                :categories="categories"
                @save="saveProduct"
                @close="closeProductModal"
            />

            <!-- Stock Adjustment Modal -->
            <stock-adjustment-modal
                v-if="showStockModal"
                :product="currentProduct"
                @save="saveStockAdjustment"
                @close="showStockModal = false"
            />

            <!-- Import Modal -->
            <import-modal
                v-if="showImportModal"
                @import="handleImport"
                @close="showImportModal = false"
            />
        </div>
    `,
    methods: {
        async loadProducts() {
            this.loading = true;
            try {
                const params = {
                    page: this.pagination.currentPage,
                    per_page: this.pagination.perPage,
                    sort_by: this.sort.key,
                    sort_direction: this.sort.direction,
                    ...this.filters
                };

                const response = await axios.get('/api/products', { params });
                this.products = response.data.data;
                this.pagination = {
                    currentPage: response.data.current_page,
                    totalPages: response.data.last_page,
                    perPage: response.data.per_page,
                    total: response.data.total
                };
            } catch (error) {
                this.$root.showToast('Failed to load products', 'error');
            } finally {
                this.loading = false;
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

        handleSort({ key, direction }) {
            this.sort = { key, direction };
            this.loadProducts();
        },

        handlePageChange(page) {
            this.pagination.currentPage = page;
            this.loadProducts();
        },

        handleSelectionChange(selected) {
            this.selectedProducts = selected;
        },

        editProduct(product) {
            this.currentProduct = { ...product };
            this.showEditModal = true;
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
                this.loadProducts();
            } catch (error) {
                this.$root.showToast(error.response?.data?.error || 'Failed to save product', 'error');
            }
        },

        async deleteProduct(product) {
            if (!confirm('Are you sure you want to delete this product?')) return;

            try {
                await axios.delete(`/api/products/${product.id}`);
                this.$root.showToast('Product deleted successfully', 'success');
                this.loadProducts();
            } catch (error) {
                this.$root.showToast('Failed to delete product', 'error');
            }
        },

        async confirmDeleteSelected() {
            if (!confirm(`Are you sure you want to delete ${this.selectedProducts.length} products?`)) return;

            try {
                await axios.post('/api/products/bulk-delete', {
                    ids: this.selectedProducts
                });
                this.$root.showToast('Products deleted successfully', 'success');
                this.selectedProducts = [];
                this.loadProducts();
            } catch (error) {
                this.$root.showToast('Failed to delete products', 'error');
            }
        },

        adjustStock(product) {
            this.currentProduct = product;
            this.showStockModal = true;
        },

        async saveStockAdjustment(adjustment) {
            try {
                await axios.post(`/api/products/${this.currentProduct.id}/stock`, adjustment);
                this.$root.showToast('Stock adjusted successfully', 'success');
                this.showStockModal = false;
                this.loadProducts();
            } catch (error) {
                this.$root.showToast('Failed to adjust stock', 'error');
            }
        },

        async handleImport(file) {
            const formData = new FormData();
            formData.append('file', file);

            try {
                await axios.post('/api/products/import', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                this.$root.showToast('Products imported successfully', 'success');
                this.showImportModal = false;
                this.loadProducts();
            } catch (error) {
                this.$root.showToast('Failed to import products', 'error');
            }
        },

        async exportProducts() {
            try {
                const response = await axios.get('/api/products/export', {
                    responseType: 'blob'
                });
                
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'products.xlsx');
                document.body.appendChild(link);
                link.click();
                link.remove();
            } catch (error) {
                this.$root.showToast('Failed to export products', 'error');
            }
        },

        closeProductModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.currentProduct = null;
        },

        getStockStatusClass(stock, minStock) {
            if (stock <= 0) return 'badge-danger';
            if (stock <= minStock) return 'badge-warning';
            return 'badge-success';
        },

        hasPermission(permission) {
            return this.$root.hasPermission(permission);
        }
    },
    created() {
        this.loadProducts();
        this.loadCategories();
    }
});
