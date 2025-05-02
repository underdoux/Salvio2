<?php
$pageTitle = 'Commission Management';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="commissions-page" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>Commission Management</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Commissions</span>
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

    <!-- Commission List Component -->
    <commission-list ref="commissionList" />

    <!-- Modals -->
    <commission-rule-modal v-if="showRuleModal"
                         @save="handleRuleSave"
                         @close="showRuleModal = false" />

    <commission-approval-modal v-if="showApprovalModal"
                            :commission="selectedCommission"
                            @approve="handleApproval"
                            @reject="handleRejection"
                            @close="closeApprovalModal" />
</div>

<script>
new Vue({
    el: '#commissions-page',
    data: {
        selectedPeriod: 'thisMonth',
        showCustomRange: false,
        customRange: {
            start: '',
            end: ''
        },
        quickPeriods: [
            { value: 'today', label: 'Today' },
            { value: 'thisWeek', label: 'This Week' },
            { value: 'thisMonth', label: 'This Month' },
            { value: 'lastMonth', label: 'Last Month' }
        ],
        showRuleModal: false,
        showApprovalModal: false,
        selectedCommission: null
    },
    methods: {
        changePeriod(period) {
            this.selectedPeriod = period;
            this.showCustomRange = false;
            this.$refs.commissionList.filters.date_range = period;
            this.$refs.commissionList.loadCommissions();
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

            this.$refs.commissionList.filters.date_range = 'custom';
            this.$refs.commissionList.customDateRange = { ...this.customRange };
            this.$refs.commissionList.loadCommissions();
        },

        handleRuleSave() {
            this.showRuleModal = false;
            this.$refs.commissionList.loadCommissions();
        },

        handleApproval(data) {
            this.closeApprovalModal();
            this.$refs.commissionList.loadCommissions();
        },

        handleRejection(data) {
            this.closeApprovalModal();
            this.$refs.commissionList.loadCommissions();
        },

        closeApprovalModal() {
            this.showApprovalModal = false;
            this.selectedCommission = null;
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
