Vue.component('import-modal', {
    data() {
        return {
            file: null,
            loading: false,
            error: '',
            preview: null,
            templateUrl: '/templates/product_import_template.xlsx',
            validationResults: null,
            importStep: 'upload', // upload, validate, confirm
            progress: 0
        };
    },
    template: `
        <div class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Import Products</h2>
                    <button class="btn-close" @click="$emit('close')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Step Progress -->
                    <div class="import-steps">
                        <div class="step" :class="{ active: importStep === 'upload' }">
                            1. Upload File
                        </div>
                        <div class="step" :class="{ active: importStep === 'validate' }">
                            2. Validate Data
                        </div>
                        <div class="step" :class="{ active: importStep === 'confirm' }">
                            3. Confirm Import
                        </div>
                    </div>

                    <!-- Upload Step -->
                    <div v-if="importStep === 'upload'" class="import-section">
                        <div class="import-instructions">
                            <h3>Instructions</h3>
                            <ol>
                                <li>Download the template file</li>
                                <li>Fill in your product data following the format</li>
                                <li>Upload the completed file</li>
                            </ol>
                            
                            <a :href="templateUrl" class="btn btn-secondary">
                                <i class="fas fa-download"></i> Download Template
                            </a>
                        </div>

                        <div class="file-upload-container">
                            <div class="upload-zone" 
                                 @drop.prevent="handleFileDrop"
                                 @dragover.prevent
                                 @click="triggerFileInput">
                                <input type="file" 
                                       ref="fileInput" 
                                       style="display: none"
                                       @change="handleFileSelect"
                                       accept=".xlsx,.xls,.csv">
                                
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drag and drop your file here or click to browse</p>
                                <p class="file-types">Supported formats: XLSX, XLS, CSV</p>
                            </div>

                            <div v-if="file" class="selected-file">
                                <i class="fas fa-file-excel"></i>
                                <span>{{ file.name }}</span>
                                <button class="btn btn-icon" @click.stop="removeFile">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div v-if="error" class="alert alert-danger">
                            {{ error }}
                        </div>

                        <div class="form-actions">
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    @click="$emit('close')">
                                Cancel
                            </button>
                            <button type="button" 
                                    class="btn btn-primary"
                                    :disabled="!file || loading"
                                    @click="validateFile">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                Validate Data
                            </button>
                        </div>
                    </div>

                    <!-- Validation Step -->
                    <div v-if="importStep === 'validate'" class="import-section">
                        <div v-if="validationResults" class="validation-results">
                            <div class="validation-summary">
                                <div class="summary-item">
                                    <label>Total Records:</label>
                                    <span>{{ validationResults.total }}</span>
                                </div>
                                <div class="summary-item">
                                    <label>Valid Records:</label>
                                    <span class="text-success">
                                        {{ validationResults.valid }}
                                    </span>
                                </div>
                                <div class="summary-item" v-if="validationResults.errors.length">
                                    <label>Errors Found:</label>
                                    <span class="text-danger">
                                        {{ validationResults.errors.length }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="validationResults.errors.length" class="validation-errors">
                                <h4>Errors</h4>
                                <div class="error-list">
                                    <div v-for="(error, index) in validationResults.errors" 
                                         :key="index"
                                         class="error-item">
                                        <span class="row-number">Row {{ error.row }}</span>
                                        <span class="error-message">{{ error.message }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="preview-table" v-if="preview">
                                <h4>Data Preview</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th v-for="header in preview.headers" 
                                                    :key="header">
                                                    {{ header }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, index) in preview.rows" 
                                                :key="index">
                                                <td v-for="(value, key) in row" 
                                                    :key="key">
                                                    {{ value }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    @click="importStep = 'upload'">
                                Back
                            </button>
                            <button type="button" 
                                    class="btn btn-primary"
                                    :disabled="!validationResults || validationResults.errors.length > 0"
                                    @click="importStep = 'confirm'">
                                Continue
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Step -->
                    <div v-if="importStep === 'confirm'" class="import-section">
                        <div class="confirm-import">
                            <h3>Confirm Import</h3>
                            <p>You are about to import {{ validationResults.valid }} products.</p>
                            <p>This action cannot be undone. Are you sure you want to proceed?</p>

                            <div v-if="progress" class="progress-bar">
                                <div class="progress" :style="{ width: progress + '%' }"></div>
                                <span>{{ progress }}%</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    @click="importStep = 'validate'"
                                    :disabled="loading">
                                Back
                            </button>
                            <button type="button" 
                                    class="btn btn-primary"
                                    :disabled="loading"
                                    @click="importProducts">
                                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                                Start Import
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
    methods: {
        triggerFileInput() {
            this.$refs.fileInput.click();
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            this.handleFile(file);
        },

        handleFileDrop(event) {
            const file = event.dataTransfer.files[0];
            this.handleFile(file);
        },

        handleFile(file) {
            if (!this.isValidFileType(file)) {
                this.error = 'Invalid file type. Please upload XLSX, XLS, or CSV file.';
                return;
            }

            this.file = file;
            this.error = '';
        },

        removeFile() {
            this.file = null;
            this.error = '';
            this.preview = null;
            this.validationResults = null;
            this.$refs.fileInput.value = '';
        },

        isValidFileType(file) {
            const validTypes = [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv'
            ];
            return validTypes.includes(file.type);
        },

        validateFile() {
            if (!this.file) return;

            this.loading = true;
            this.error = '';

            const formData = new FormData();
            formData.append('file', this.file);

            axios.post('/api/products/validate-import', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                this.validationResults = response.data.validation;
                this.preview = response.data.preview;
                this.importStep = 'validate';
            })
            .catch(error => {
                this.error = error.response?.data?.error || 'Failed to validate file';
            })
            .finally(() => {
                this.loading = false;
            });
        },

        importProducts() {
            this.loading = true;
            this.progress = 0;

            const formData = new FormData();
            formData.append('file', this.file);

            axios.post('/api/products/import', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: progressEvent => {
                    this.progress = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    );
                }
            })
            .then(response => {
                this.$root.showToast(
                    `Successfully imported ${response.data.imported} products`,
                    'success'
                );
                this.$emit('close');
            })
            .catch(error => {
                this.error = error.response?.data?.error || 'Import failed';
            })
            .finally(() => {
                this.loading = false;
            });
        }
    }
});
