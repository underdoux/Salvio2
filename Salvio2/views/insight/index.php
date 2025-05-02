<?php
$pageTitle = 'Business Insights';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="insights-page" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>Business Insights</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Insights</span>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="date-filter">
                <button class="btn" 
                        :class="{ 'btn-primary': selectedPeriod === period.value }"
                        v-for="period in periods"
                        :key="period.value"
                        @click="changePeriod(period.value)">
                    {{ period.label }}
                </button>
                <button class="btn" 
                        :class="{ 'btn-primary': showCustomRange }"
                        @click="showCustomRange = !showCustomRange">
                    <i class="fas fa-calendar"></i> Custom
                </button>
            </div>
        </div>
    </div>

    <!-- Custom Date Range -->
    <div v-if="showCustomRange" class="custom-range-picker">
        <div class="date-inputs">
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" v-model="customRange.start" @change="applyCustomRange">
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="date" v-model="customRange.end" @change="applyCustomRange">
            </div>
        </div>
        <button class="btn btn-primary" @click="applyCustomRange">
            Apply Range
        </button>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div v-for="kpi in insights.performance.kpis" 
             :key="kpi.name"
             class="kpi-card"
             :class="{ 'positive': kpi.growth > 0, 'negative': kpi.growth < 0 }">
            <div class="kpi-icon">
                <i :class="['fas', kpi.icon]"></i>
            </div>
            <div class="kpi-content">
                <h3>{{ kpi.label }}</h3>
                <div class="kpi-value">{{ formatValue(kpi.current, kpi.format) }}</div>
                <div class="kpi-trend">
                    <i :class="['fas', kpi.growth > 0 ? 'fa-arrow-up' : 'fa-arrow-down']"></i>
                    {{ Math.abs(kpi.growth).toFixed(1) }}%
                    <span class="period">vs previous period</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="insights-grid">
        <!-- Sales Trends -->
        <div class="insight-card">
            <div class="card-header">
                <h3>Sales Trends</h3>
                <div class="card-actions">
                    <button class="btn btn-icon" @click="exportChart('sales')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas ref="salesChart"></canvas>
            </div>
        </div>

        <!-- Product Performance -->
        <div class="insight-card">
            <div class="card-header">
                <h3>Product Performance</h3>
                <div class="card-actions">
                    <button class="btn btn-icon" @click="exportChart('products')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas ref="productChart"></canvas>
            </div>
        </div>

        <!-- Customer Segments -->
        <div class="insight-card">
            <div class="card-header">
                <h3>Customer Segments</h3>
                <div class="card-actions">
                    <button class="btn btn-icon" @click="exportChart('customers')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas ref="customerChart"></canvas>
            </div>
        </div>

        <!-- Category Distribution -->
        <div class="insight-card">
            <div class="card-header">
                <h3>Category Distribution</h3>
                <div class="card-actions">
                    <button class="btn btn-icon" @click="exportChart('categories')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas ref="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Analysis Sections -->
    <div class="analysis-sections">
        <!-- Best Sellers -->
        <div class="analysis-card">
            <div class="card-header">
                <h3>Best Selling Products</h3>
                <div class="card-actions">
                    <button class="btn btn-text" @click="showProductDetails">
                        View All <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Growth</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in insights.products.best_sellers" :key="product.id">
                                <td>{{ product.name }}</td>
                                <td>{{ product.category_name }}</td>
                                <td>{{ product.order_count }}</td>
                                <td>{{ formatCurrency(product.total_revenue) }}</td>
                                <td :class="{ 'positive': product.growth > 0, 'negative': product.growth < 0 }">
                                    <i :class="['fas', product.growth > 0 ? 'fa-arrow-up' : 'fa-arrow-down']"></i>
                                    {{ Math.abs(product.growth).toFixed(1) }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Customer Retention -->
        <div class="analysis-card">
            <div class="card-header">
                <h3>Customer Retention</h3>
                <div class="card-actions">
                    <button class="btn btn-text" @click="showRetentionDetails">
                        View Details <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="retention-heatmap">
                    <canvas ref="retentionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Sales Performance -->
        <div class="analysis-card">
            <div class="card-header">
                <h3>Sales Performance</h3>
                <div class="card-actions">
                    <button class="btn btn-text" @click="showSalesDetails">
                        View Details <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="performance-grid">
                    <div v-for="person in insights.performance.sales_performance" 
                         :key="person.id"
                         class="performance-card">
                        <div class="person-info">
                            <h4>{{ person.name }}</h4>
                            <span class="title">Sales Representative</span>
                        </div>
                        <div class="stats">
                            <div class="stat-item">
                                <label>Orders</label>
                                <strong>{{ person.total_orders }}</strong>
                            </div>
                            <div class="stat-item">
                                <label>Revenue</label>
                                <strong>{{ formatCurrency(person.total_revenue) }}</strong>
                            </div>
                            <div class="stat-item">
                                <label>Customers</label>
                                <strong>{{ person.unique_customers }}</strong>
                            </div>
                        </div>
                        <div class="trend">
                            <canvas :ref="'performanceChart' + person.id"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Vue({
    el: '#insights-page',
    data: {
        insights: <?= json_encode($insights) ?>,
        selectedPeriod: 'this_month',
        showCustomRange: false,
        customRange: {
            start: '',
            end: ''
        },
        periods: [
            { value: 'today', label: 'Today' },
            { value: 'yesterday', label: 'Yesterday' },
            { value: 'this_week', label: 'This Week' },
            { value: 'last_week', label: 'Last Week' },
            { value: 'this_month', label: 'This Month' },
            { value: 'last_month', label: 'Last Month' }
        ],
        charts: {}
    },
    mounted() {
        this.initializeCharts();
        this.startAutoRefresh();
    },
    beforeDestroy() {
        this.stopAutoRefresh();
    },
    methods: {
        initializeCharts() {
            this.initializeSalesChart();
            this.initializeProductChart();
            this.initializeCustomerChart();
            this.initializeCategoryChart();
            this.initializeRetentionChart();
            this.initializePerformanceCharts();
        },
        initializeSalesChart() {
            const ctx = this.$refs.salesChart.getContext('2d');
            this.charts.sales = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.insights.sales.trend.map(d => d.date),
                    datasets: [{
                        label: 'Revenue',
                        data: this.insights.sales.trend.map(d => d.total_revenue),
                        borderColor: '#4CAF50',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        },
        // ... Similar methods for other charts ...
        
        changePeriod(period) {
            this.selectedPeriod = period;
            this.showCustomRange = false;
            this.refreshData();
        },

        applyCustomRange() {
            if (!this.customRange.start || !this.customRange.end) {
                this.$root.showToast('Please select both start and end dates', 'warning');
                return;
            }

            if (this.customRange.start > this.customRange.end) {
                this.$root.showToast('Start date cannot be after end date', 'error');
                return;
            }

            this.refreshData();
        },

        async refreshData() {
            try {
                const params = {
                    period: this.selectedPeriod,
                    start_date: this.customRange.start,
                    end_date: this.customRange.end
                };

                const response = await axios.get('/insights/data', { params });
                this.insights = response.data;
                this.updateCharts();
            } catch (error) {
                this.$root.showToast('Failed to refresh insights', 'error');
            }
        },

        updateCharts() {
            Object.values(this.charts).forEach(chart => {
                chart.update();
            });
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(value);
        },

        formatValue(value, format) {
            switch (format) {
                case 'currency':
                    return this.formatCurrency(value);
                case 'number':
                    return new Intl.NumberFormat().format(value);
                case 'percentage':
                    return value.toFixed(1) + '%';
                default:
                    return value;
            }
        },

        exportChart(type) {
            window.location.href = `/insights/export?type=${type}&period=${this.selectedPeriod}`;
        },

        startAutoRefresh() {
            this.refreshInterval = setInterval(() => {
                this.refreshData();
            }, 300000); // 5 minutes
        },

        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        }
    }
});
</script>
