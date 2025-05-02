<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="reports-page" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>Reports</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Reports</span>
            </nav>
        </div>
    </div>

    <!-- Report Types Grid -->
    <div class="report-types-grid">
        <div v-for="type in reportTypes" 
             :key="type.id" 
             class="report-type-card"
             @click="selectReportType(type)">
            <div class="card-icon">
                <i :class="['fas', type.icon]"></i>
            </div>
            <div class="card-content">
                <h3>{{ type.name }}</h3>
                <p>{{ type.description }}</p>
            </div>
        </div>
    </div>

    <!-- Report Parameters Modal -->
    <div v-if="showParamsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ selectedType.name }} Parameters</h2>
                <button class="close-btn" @click="showParamsModal = false">×</button>
            </div>
            
            <div class="modal-body">
                <!-- Date Range -->
                <div class="form-group">
                    <label>Date Range</label>
                    <div class="date-range">
                        <div class="quick-ranges">
                            <button v-for="range in dateRanges"
                                    :key="range.value"
                                    :class="{ active: params.dateRange === range.value }"
                                    @click="selectDateRange(range.value)">
                                {{ range.label }}
                            </button>
                        </div>
                        <div v-if="params.dateRange === 'custom'" class="custom-range">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" v-model="params.startDate">
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" v-model="params.endDate">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report-specific Parameters -->
                <template v-if="selectedType.id === 'sales'">
                    <div class="form-group">
                        <label>Status</label>
                        <select v-model="params.status">
                            <option value="">All Statuses</option>
                            <option value="new">New</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select v-model="params.paymentStatus">
                            <option value="">All Payment Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sales Person</label>
                        <select v-model="params.userId">
                            <option value="">All Sales People</option>
                            <option v-for="user in salesPeople"
                                    :key="user.id"
                                    :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                </template>

                <template v-if="selectedType.id === 'inventory'">
                    <div class="form-group">
                        <label>Category</label>
                        <select v-model="params.categoryId">
                            <option value="">All Categories</option>
                            <option v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Filters</label>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" v-model="params.lowStock">
                                Show Low Stock Items Only
                            </label>
                            <label>
                                <input type="checkbox" v-model="params.byOrder">
                                Show By-Order Items Only
                            </label>
                        </div>
                    </div>
                </template>

                <template v-if="selectedType.id === 'commissions'">
                    <div class="form-group">
                        <label>Status</label>
                        <select v-model="params.status">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="paid">Paid</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sales Person</label>
                        <select v-model="params.userId">
                            <option value="">All Sales People</option>
                            <option v-for="user in salesPeople"
                                    :key="user.id"
                                    :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                </template>

                <template v-if="selectedType.id === 'profits'">
                    <div class="form-group">
                        <label>Status</label>
                        <select v-model="params.status">
                            <option value="">All Statuses</option>
                            <option value="calculated">Calculated</option>
                            <option value="distributed">Distributed</option>
                        </select>
                    </div>
                </template>

                <!-- Report Format -->
                <div class="form-group">
                    <label>Report Format</label>
                    <select v-model="params.format">
                        <option value="xlsx">Excel (XLSX)</option>
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>

                <!-- Schedule Options -->
                <div class="form-group">
                    <label>
                        <input type="checkbox" v-model="params.schedule">
                        Schedule this report
                    </label>
                </div>

                <div v-if="params.schedule" class="schedule-options">
                    <div class="form-group">
                        <label>Frequency</label>
                        <select v-model="params.frequency">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Recipients (Email)</label>
                        <tags-input v-model="params.recipients" 
                                  :validate="validateEmail"
                                  placeholder="Enter email and press Enter"></tags-input>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" @click="showParamsModal = false">
                    Cancel
                </button>
                <button class="btn btn-primary" @click="generateReport" :disabled="isGenerating">
                    <i class="fas fa-spinner fa-spin" v-if="isGenerating"></i>
                    {{ isGenerating ? 'Generating...' : 'Generate Report' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Report Preview</h2>
                <button class="close-btn" @click="showPreviewModal = false">×</button>
            </div>
            
            <div class="modal-body">
                <div v-if="preview" class="preview-content">
                    <!-- Preview content will be dynamically rendered based on report type -->
                    <component :is="previewComponent"
                             :data="preview"
                             :type="selectedType.id">
                    </component>
                </div>
                <div v-else class="preview-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading preview...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Vue({
    el: '#reports-page',
    data: {
        reportTypes: <?= json_encode($reportTypes) ?>,
        selectedType: null,
        showParamsModal: false,
        showPreviewModal: false,
        isGenerating: false,
        preview: null,
        params: {
            dateRange: 'this_month',
            startDate: '',
            endDate: '',
            status: '',
            paymentStatus: '',
            userId: '',
            categoryId: '',
            lowStock: false,
            byOrder: false,
            format: 'xlsx',
            schedule: false,
            frequency: 'monthly',
            recipients: []
        },
        dateRanges: [
            { value: 'today', label: 'Today' },
            { value: 'yesterday', label: 'Yesterday' },
            { value: 'this_week', label: 'This Week' },
            { value: 'last_week', label: 'Last Week' },
            { value: 'this_month', label: 'This Month' },
            { value: 'last_month', label: 'Last Month' },
            { value: 'this_year', label: 'This Year' },
            { value: 'custom', label: 'Custom' }
        ],
        salesPeople: [],
        categories: []
    },
    computed: {
        previewComponent() {
            return this.selectedType ? this.selectedType.id + '-preview' : null;
        }
    },
    methods: {
        selectReportType(type) {
            this.selectedType = type;
            this.showParamsModal = true;
            this.loadDependencies();
        },

        async loadDependencies() {
            if (this.selectedType.id === 'sales' || this.selectedType.id === 'commissions') {
                const response = await axios.get('/api/users/sales');
                this.salesPeople = response.data;
            }
            if (this.selectedType.id === 'inventory') {
                const response = await axios.get('/api/categories');
                this.categories = response.data;
            }
        },

        selectDateRange(range) {
            this.params.dateRange = range;
            if (range !== 'custom') {
                const dates = this.calculateDateRange(range);
                this.params.startDate = dates.start;
                this.params.endDate = dates.end;
            }
        },

        calculateDateRange(range) {
            const today = new Date();
            let start = new Date();
            let end = new Date();

            switch (range) {
                case 'today':
                    break;
                case 'yesterday':
                    start.setDate(start.getDate() - 1);
                    end = new Date(start);
                    break;
                case 'this_week':
                    start.setDate(start.getDate() - start.getDay());
                    break;
                case 'last_week':
                    start.setDate(start.getDate() - start.getDay() - 7);
                    end.setDate(end.getDate() - end.getDay() - 1);
                    break;
                case 'this_month':
                    start.setDate(1);
                    break;
                case 'last_month':
                    start.setMonth(start.getMonth() - 1);
                    start.setDate(1);
                    end.setDate(0);
                    break;
                case 'this_year':
                    start.setMonth(0, 1);
                    break;
            }

            return {
                start: start.toISOString().split('T')[0],
                end: end.toISOString().split('T')[0]
            };
        },

        validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        async generateReport() {
            try {
                this.isGenerating = true;

                // Get preview first
                const previewResponse = await axios.get('/reports/preview', {
                    params: {
                        type: this.selectedType.id,
                        ...this.params
                    }
                });
                this.preview = previewResponse.data;
                this.showPreviewModal = true;

                if (this.params.schedule) {
                    // Save schedule
                    await axios.post('/reports/schedule', {
                        type: this.selectedType.id,
                        frequency: this.params.frequency,
                        params: this.params,
                        recipients: this.params.recipients
                    });
                    this.$root.showToast('Report scheduled successfully', 'success');
                } else {
                    // Generate and download report
                    window.location.href = '/reports/generate?' + new URLSearchParams({
                        type: this.selectedType.id,
                        ...this.params
                    });
                }

            } catch (error) {
                this.$root.showToast(
                    error.response?.data?.message || 'Failed to generate report',
                    'error'
                );
            } finally {
                this.isGenerating = false;
                this.showParamsModal = false;
            }
        }
    }
});
</script>

<style>
.report-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.report-type-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    align-items: center;
}

.report-type-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card-icon {
    font-size: 2em;
    color: #007bff;
    margin-right: 20px;
    width: 50px;
    text-align: center;
}

.card-content h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.card-content p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.date-range {
    margin-bottom: 20px;
}

.quick-ranges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.quick-ranges button {
    padding: 8px 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-ranges button.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}

.custom-range {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 15px;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
}

.schedule-options {
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.preview-content {
    max-height: 500px;
    overflow-y: auto;
}

.preview-loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.preview-loading i {
    font-size: 2em;
    margin-bottom: 10px;
}
</style>
