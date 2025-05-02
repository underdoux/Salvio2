// Sales Report Preview Component
Vue.component('sales-preview', {
    props: ['data', 'type'],
    template: `
        <div class="report-preview">
            <div class="preview-section">
                <h3>Sales Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>Total Orders</label>
                        <strong>{{ data.total_orders }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Total Revenue</label>
                        <strong>{{ formatCurrency(data.total_revenue) }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Total Tax</label>
                        <strong>{{ formatCurrency(data.total_tax) }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Average Order Value</label>
                        <strong>{{ formatCurrency(data.average_order_value) }}</strong>
                    </div>
                </div>
            </div>

            <div class="preview-section">
                <h3>Status Breakdown</h3>
                <div class="status-chart">
                    <canvas ref="statusChart"></canvas>
                </div>
            </div>

            <div class="preview-section">
                <h3>Payment Status</h3>
                <div class="status-chart">
                    <canvas ref="paymentChart"></canvas>
                </div>
            </div>
        </div>
    `,
    mounted() {
        this.renderCharts();
    },
    methods: {
        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(value);
        },
        renderCharts() {
            // Status breakdown chart
            new Chart(this.$refs.statusChart, {
                type: 'pie',
                data: {
                    labels: Object.keys(this.data.status_breakdown),
                    datasets: [{
                        data: Object.values(this.data.status_breakdown),
                        backgroundColor: ['#4CAF50', '#2196F3', '#FFC107', '#F44336']
                    }]
                }
            });

            // Payment status chart
            new Chart(this.$refs.paymentChart, {
                type: 'pie',
                data: {
                    labels: Object.keys(this.data.payment_status_breakdown),
                    datasets: [{
                        data: Object.values(this.data.payment_status_breakdown),
                        backgroundColor: ['#4CAF50', '#FFC107', '#F44336', '#9C27B0']
                    }]
                }
            });
        }
    }
});

// Inventory Report Preview Component
Vue.component('inventory-preview', {
    props: ['data', 'type'],
    template: `
        <div class="report-preview">
            <div class="preview-section">
                <h3>Inventory Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>Total Products</label>
                        <strong>{{ data.total_products }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Total Stock Value</label>
                        <strong>{{ formatCurrency(data.total_stock_value) }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Low Stock Items</label>
                        <strong>{{ data.low_stock_items }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>By-Order Items</label>
                        <strong>{{ data.by_order_items }}</strong>
                    </div>
                </div>
            </div>

            <div class="preview-section">
                <h3>Category Distribution</h3>
                <div class="category-chart">
                    <canvas ref="categoryChart"></canvas>
                </div>
            </div>
        </div>
    `,
    mounted() {
        this.renderCharts();
    },
    methods: {
        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(value);
        },
        renderCharts() {
            new Chart(this.$refs.categoryChart, {
                type: 'bar',
                data: {
                    labels: Object.keys(this.data.category_breakdown),
                    datasets: [{
                        label: 'Products per Category',
                        data: Object.values(this.data.category_breakdown),
                        backgroundColor: '#2196F3'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
});

// Commission Report Preview Component
Vue.component('commissions-preview', {
    props: ['data', 'type'],
    template: `
        <div class="report-preview">
            <div class="preview-section">
                <h3>Commission Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>Total Commissions</label>
                        <strong>{{ data.total_commissions }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Total Amount</label>
                        <strong>{{ formatCurrency(data.total_amount) }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Average Commission</label>
                        <strong>{{ formatCurrency(data.average_commission) }}</strong>
                    </div>
                </div>
            </div>

            <div class="preview-section">
                <h3>Status Breakdown</h3>
                <div class="status-chart">
                    <canvas ref="statusChart"></canvas>
                </div>
            </div>

            <div class="preview-section">
                <h3>Top Sales People</h3>
                <div class="sales-chart">
                    <canvas ref="salesChart"></canvas>
                </div>
            </div>
        </div>
    `,
    mounted() {
        this.renderCharts();
    },
    methods: {
        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(value);
        },
        renderCharts() {
            // Status breakdown chart
            new Chart(this.$refs.statusChart, {
                type: 'pie',
                data: {
                    labels: Object.keys(this.data.status_breakdown),
                    datasets: [{
                        data: Object.values(this.data.status_breakdown),
                        backgroundColor: ['#4CAF50', '#2196F3', '#F44336']
                    }]
                }
            });

            // Sales performance chart
            const salesData = this.data.sales_person_breakdown;
            new Chart(this.$refs.salesChart, {
                type: 'bar',
                data: {
                    labels: Object.keys(salesData),
                    datasets: [{
                        label: 'Commission Amount',
                        data: Object.values(salesData).map(d => d.amount),
                        backgroundColor: '#2196F3'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => this.formatCurrency(value)
                            }
                        }
                    }
                }
            });
        }
    }
});

// Profit Report Preview Component
Vue.component('profits-preview', {
    props: ['data', 'type'],
    template: `
        <div class="report-preview">
            <div class="preview-section">
                <h3>Profit Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>Total Distributions</label>
                        <strong>{{ data.total_distributions }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Total Revenue</label>
                        <strong>{{ formatCurrency(data.total_revenue) }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Total Costs</label>
                        <strong>{{ formatCurrency(data.total_costs) }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Total Profit</label>
                        <strong>{{ formatCurrency(data.total_profit) }}</strong>
                    </div>
                    <div class="summary-item">
                        <label>Average Profit Margin</label>
                        <strong>{{ data.average_profit_margin.toFixed(2) }}%</strong>
                    </div>
                </div>
            </div>

            <div class="preview-section">
                <h3>Status Breakdown</h3>
                <div class="status-chart">
                    <canvas ref="statusChart"></canvas>
                </div>
            </div>

            <div class="preview-section">
                <h3>Profit Trend</h3>
                <div class="trend-chart">
                    <canvas ref="trendChart"></canvas>
                </div>
            </div>
        </div>
    `,
    mounted() {
        this.renderCharts();
    },
    methods: {
        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(value);
        },
        renderCharts() {
            // Status breakdown chart
            new Chart(this.$refs.statusChart, {
                type: 'pie',
                data: {
                    labels: Object.keys(this.data.status_breakdown),
                    datasets: [{
                        data: Object.values(this.data.status_breakdown),
                        backgroundColor: ['#4CAF50', '#2196F3']
                    }]
                }
            });

            // Profit trend chart (assuming data includes trend information)
            if (this.data.trend) {
                new Chart(this.$refs.trendChart, {
                    type: 'line',
                    data: {
                        labels: this.data.trend.map(t => t.period),
                        datasets: [{
                            label: 'Net Profit',
                            data: this.data.trend.map(t => t.profit),
                            borderColor: '#4CAF50',
                            fill: false
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: value => this.formatCurrency(value)
                                }
                            }
                        }
                    }
                });
            }
        }
    }
});
