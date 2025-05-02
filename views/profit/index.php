<?php
$pageTitle = 'Profit Distribution';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="profits-page" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>Profit Distribution</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Profit Distribution</span>
            </nav>
        </div>
        
        <div class="header-right">
            <div class="date-filter">
                <button class="btn" 
                        :class="{ 'btn-primary': selectedPeriod === period.value }"
                        v-for="period in quickPeriods"
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
                <input type="date" 
                       v-model="customRange.start"
                       @change="applyCustomRange">
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="date" 
                       v-model="customRange.end"
                       @change="applyCustomRange">
            </div>
        </div>
        <button class="btn btn-primary" @click="applyCustomRange">
            Apply Range
        </button>
    </div>

    <!-- Profit List Component -->
    <profit-list ref="profitList" />

    <!-- Modals -->
    <profit-calculation-modal v-if="showCalculationModal"
                           @calculate="handleCalculation"
                           @close="showCalculationModal = false" />

    <profit-distribution-modal v-if="showDistributionModal"
                            :distribution="selectedDistribution"
                            @distribute="handleDistribution"
                            @close="closeDistributionModal" />

    <investor-modal v-if="showInvestorModal"
                   @close="showInvestorModal = false" />
</div>

<script>
new Vue({
    el: '#profits-page',
    data: {
        selectedPeriod: 'thisMonth',
        showCustomRange: false,
        customRange: {
            start: '',
            end: ''
        },
        quickPeriods: [
            { value: 'thisMonth', label: 'This Month' },
            { value: 'lastMonth', label: 'Last Month' },
            { value: 'lastQuarter', label: 'Last Quarter' },
            { value: 'thisYear', label: 'This Year' }
        ],
        showCalculationModal: false,
        showDistributionModal: false,
        showInvestorModal: false,
        selectedDistribution: null
    },
    methods: {
        changePeriod(period) {
            this.selectedPeriod = period;
            this.showCustomRange = false;
            this.$refs.profitList.filters.date_range = period;
            this.$refs.profitList.loadDistributions();
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

            this.$refs.profitList.filters.date_range = 'custom';
            this.$refs.profitList.customDateRange = { ...this.customRange };
            this.$refs.profitList.loadDistributions();
        },

        handleCalculation() {
            this.showCalculationModal = false;
            this.$refs.profitList.loadDistributions();
        },

        handleDistribution() {
            this.closeDistributionModal();
            this.$refs.profitList.loadDistributions();
        },

        closeDistributionModal() {
            this.showDistributionModal = false;
            this.selectedDistribution = null;
        }
    },
    created() {
        // Set default date range for custom picker
        const today = new Date();
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        
        this.customRange.start = firstDayOfMonth.toISOString().split('T')[0];
        this.customRange.end = today.toISOString().split('T')[0];
    }
});
</script>
