<?php
$pageTitle = 'Backup Management';
require_once __DIR__ . '/../layouts/main.php';
?>

<div id="backup-page" v-cloak>
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>Backup Management</h1>
            <nav class="breadcrumb">
                <a href="/dashboard">Dashboard</a>
                <span class="separator">/</span>
                <span class="current">Backups</span>
            </nav>
        </div>
        
        <div class="header-right">
            <button class="btn btn-primary" @click="createBackup" :disabled="isCreating">
                <i class="fas" :class="isCreating ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
                {{ isCreating ? 'Creating...' : 'Create Backup' }}
            </button>
        </div>
    </div>

    <!-- Backup Overview -->
    <div class="overview-cards">
        <div class="card">
            <div class="card-body">
                <h3>Total Backups</h3>
                <div class="stat">{{ backups.length }}</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3>Last Backup</h3>
                <div class="stat">{{ getLastBackupTime() }}</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3>Storage Used</h3>
                <div class="stat">{{ getTotalStorageUsed() }}</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3>Next Scheduled</h3>
                <div class="stat">{{ getNextScheduledBackup() }}</div>
            </div>
        </div>
    </div>

    <!-- Backup List -->
    <div class="section">
        <div class="section-header">
            <h2>Backup History</h2>
            <div class="section-actions">
                <button class="btn btn-text" @click="refreshBackups">
                    <i class="fas fa-sync"></i> Refresh
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Backup ID</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Last Restored</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="backup in backups" :key="backup.backup_id">
                        <td>{{ backup.backup_id }}</td>
                        <td>
                            <span class="badge" :class="'badge-' + backup.type">
                                {{ backup.type }}
                            </span>
                        </td>
                        <td>
                            {{ formatDate(backup.created_at) }}
                            <div class="text-muted">by {{ backup.created_by }}</div>
                        </td>
                        <td>{{ formatSize(backup.size) }}</td>
                        <td>
                            <span class="badge" 
                                  :class="backup.status === 'success' ? 'badge-success' : 'badge-danger'">
                                {{ backup.status }}
                            </span>
                        </td>
                        <td>
                            <template v-if="backup.last_restored">
                                {{ formatDate(backup.last_restored) }}
                                <div class="text-muted">by {{ backup.restored_by }}</div>
                            </template>
                            <span v-else>Never</span>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-icon" 
                                        @click="downloadBackup(backup)"
                                        title="Download">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn btn-icon" 
                                        @click="showRestoreModal(backup)"
                                        title="Restore">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button class="btn btn-icon" 
                                        @click="deleteBackup(backup.backup_id)"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Backup Schedule -->
    <div class="section">
        <div class="section-header">
            <h2>Backup Schedule</h2>
            <div class="section-actions">
                <button class="btn btn-secondary" @click="showAddScheduleModal">
                    <i class="fas fa-plus"></i> Add Schedule
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Frequency</th>
                        <th>Time</th>
                        <th>Next Run</th>
                        <th>Retention</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="schedule in schedules" :key="schedule.id">
                        <td>
                            <span class="badge" :class="'badge-' + schedule.type">
                                {{ schedule.type }}
                            </span>
                        </td>
                        <td>
                            {{ schedule.frequency }}
                            <div class="text-muted" v-if="schedule.frequency === 'weekly'">
                                {{ getDayName(schedule.day_of_week) }}
                            </div>
                            <div class="text-muted" v-if="schedule.frequency === 'monthly'">
                                Day {{ schedule.day_of_month }}
                            </div>
                        </td>
                        <td>{{ formatTime(schedule.time_of_day) }}</td>
                        <td>{{ formatDate(schedule.next_run) }}</td>
                        <td>{{ schedule.retention_days }} days</td>
                        <td>
                            <div class="toggle-switch">
                                <input type="checkbox" 
                                       :id="'schedule-' + schedule.id"
                                       v-model="schedule.is_active"
                                       @change="toggleSchedule(schedule)">
                                <label :for="'schedule-' + schedule.id"></label>
                            </div>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-icon" 
                                        @click="editSchedule(schedule)"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-icon" 
                                        @click="deleteSchedule(schedule.id)"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Storage Locations -->
    <div class="section">
        <div class="section-header">
            <h2>Storage Locations</h2>
            <div class="section-actions">
                <button class="btn btn-secondary" @click="showAddStorageModal">
                    <i class="fas fa-plus"></i> Add Storage
                </button>
            </div>
        </div>

        <div class="storage-grid">
            <div v-for="storage in storageLocations" 
                 :key="storage.id" 
                 class="storage-card">
                <div class="storage-icon">
                    <i class="fas" :class="getStorageIcon(storage.type)"></i>
                </div>
                <div class="storage-info">
                    <h3>{{ storage.name }}</h3>
                    <div class="storage-type">{{ storage.type }}</div>
                    <div class="storage-status" :class="storage.is_active ? 'active' : 'inactive'">
                        {{ storage.is_active ? 'Active' : 'Inactive' }}
                    </div>
                </div>
                <div class="storage-actions">
                    <button class="btn btn-icon" 
                            @click="testStorage(storage.id)"
                            title="Test Connection">
                        <i class="fas fa-vial"></i>
                    </button>
                    <button class="btn btn-icon" 
                            @click="editStorage(storage)"
                            title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-icon" 
                            @click="deleteStorage(storage.id)"
                            title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Backup Modal -->
    <div v-if="showBackupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create Backup</h3>
                <button class="close-btn" @click="showBackupModal = false">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Backup Type</label>
                    <select v-model="newBackup.type" class="form-control">
                        <option value="full">Full Backup</option>
                        <option value="database">Database Only</option>
                        <option value="files">Files Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Storage Locations</label>
                    <div class="storage-checkboxes">
                        <label v-for="storage in activeStorageLocations" :key="storage.id">
                            <input type="checkbox" 
                                   v-model="newBackup.storage_ids" 
                                   :value="storage.id">
                            {{ storage.name }}
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="showBackupModal = false">
                    Cancel
                </button>
                <button class="btn btn-primary" @click="confirmCreateBackup">
                    Create Backup
                </button>
            </div>
        </div>
    </div>

    <!-- Restore Modal -->
    <div v-if="showRestoreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Restore Backup</h3>
                <button class="close-btn" @click="showRestoreModal = false">×</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Warning: Restoring a backup will overwrite current data. This action cannot be undone.
                </div>
                <div class="form-group">
                    <label>Backup Details</label>
                    <div class="backup-details" v-if="selectedBackup">
                        <div class="detail-row">
                            <span class="label">ID:</span>
                            <span class="value">{{ selectedBackup.backup_id }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Created:</span>
                            <span class="value">{{ formatDate(selectedBackup.created_at) }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Type:</span>
                            <span class="value">{{ selectedBackup.type }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Size:</span>
                            <span class="value">{{ formatSize(selectedBackup.size) }}</span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Options</label>
                    <div class="restore-options">
                        <label>
                            <input type="checkbox" v-model="restoreOptions.skip_database">
                            Skip Database Restore
                        </label>
                        <label>
                            <input type="checkbox" v-model="restoreOptions.skip_files">
                            Skip Files Restore
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="showRestoreModal = false">
                    Cancel
                </button>
                <button class="btn btn-danger" @click="confirmRestore">
                    Restore Backup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
new Vue({
    el: '#backup-page',
    data: {
        backups: <?= json_encode($backups) ?>,
        schedules: <?= json_encode($schedules) ?>,
        storageLocations: <?= json_encode($storageLocations) ?>,
        showBackupModal: false,
        showRestoreModal: false,
        isCreating: false,
        selectedBackup: null,
        newBackup: {
            type: 'full',
            storage_ids: []
        },
        restoreOptions: {
            skip_database: false,
            skip_files: false
        }
    },
    computed: {
        activeStorageLocations() {
            return this.storageLocations.filter(s => s.is_active);
        }
    },
    methods: {
        async refreshBackups() {
            try {
                const response = await axios.get('/backups');
                this.backups = response.data;
            } catch (error) {
                this.$root.showToast('Failed to refresh backups', 'error');
            }
        },

        async createBackup() {
            this.showBackupModal = true;
        },

        async confirmCreateBackup() {
            if (this.isCreating) return;

            try {
                this.isCreating = true;
                const response = await axios.post('/backups/create', this.newBackup);
                this.backups.unshift(response.data.data);
                this.showBackupModal = false;
                this.$root.showToast('Backup created successfully', 'success');
            } catch (error) {
                this.$root.showToast(
                    error.response?.data?.message || 'Failed to create backup',
                    'error'
                );
            } finally {
                this.isCreating = false;
            }
        },

        async downloadBackup(backup) {
            window.location.href = `/backups/download?id=${backup.backup_id}`;
        },

        showRestoreModal(backup) {
            this.selectedBackup = backup;
            this.restoreOptions = {
                skip_database: false,
                skip_files: false
            };
            this.showRestoreModal = true;
        },

        async confirmRestore() {
            if (!confirm('Are you sure you want to restore this backup? This action cannot be undone.')) {
                return;
            }

            try {
                await axios.post('/backups/restore', {
                    backup_id: this.selectedBackup.backup_id,
                    ...this.restoreOptions
                });
                this.showRestoreModal = false;
                this.$root.showToast('Backup restored successfully', 'success');
                this.refreshBackups();
            } catch (error) {
                this.$root.showToast('Failed to restore backup', 'error');
            }
        },

        async deleteBackup(backupId) {
            if (!confirm('Are you sure you want to delete this backup?')) {
                return;
            }

            try {
                await axios.delete(`/backups/${backupId}`);
                this.backups = this.backups.filter(b => b.backup_id !== backupId);
                this.$root.showToast('Backup deleted successfully', 'success');
            } catch (error) {
                this.$root.showToast('Failed to delete backup', 'error');
            }
        },

        formatDate(date) {
            if (!date) return 'Never';
            return new Date(date).toLocaleString();
        },

        formatTime(time) {
            return time;
        },

        formatSize(bytes) {
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            let size = bytes;
            let unit = 0;
            while (size >= 1024 && unit < units.length - 1) {
                size /= 1024;
                unit++;
            }
            return `${size.toFixed(2)} ${units[unit]}`;
        },

        getDayName(day) {
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            return days[day - 1];
        },

        getStorageIcon(type) {
            const icons = {
                local: 'fa-hdd',
                ftp: 'fa-server',
                s3: 'fa-cloud',
                google_drive: 'fa-google-drive'
            };
            return icons[type] || 'fa-question';
        },

        getLastBackupTime() {
            if (!this.backups.length) return 'Never';
            return this.formatDate(this.backups[0].created_at);
        },

        getTotalStorageUsed() {
            const total = this.backups.reduce((sum, backup) => sum + backup.size, 0);
            return this.formatSize(total);
        },

        getNextScheduledBackup() {
            const nextSchedule = this.schedules
                .filter(s => s.is_active)
                .sort((a, b) => new Date(a.next_run) - new Date(b.next_run))[0];
            return nextSchedule ? this.formatDate(nextSchedule.next_run) : 'No schedules';
        }
    }
});
</script>
