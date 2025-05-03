<div class="commission-rates-page">
    <div class="page-header">
        <h2>Commission Rates</h2>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="showRateModal()">
                <i class="fas fa-plus"></i> Add New Rate
            </button>
            <a href="<?= $baseUrl ?>/commissions" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Commissions
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Commission Rates Table -->
    <div class="rates-sections">
        <!-- Global Rates -->
        <div class="rates-section">
            <h3>Global Rates</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Rate %</th>
                            <th>Min Amount</th>
                            <th>Max Amount</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $globalRates = array_filter($rates, function($rate) {
                            return $rate['type'] === 'global';
                        });
                        if (empty($globalRates)): 
                        ?>
                            <tr>
                                <td colspan="6" class="text-center">No global rates defined</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($globalRates as $rate): ?>
                                <tr>
                                    <td><?= $rate['rate_percent'] ?>%</td>
                                    <td>
                                        <?= $rate['min_amount'] ? CurrencyFormatter::getInstance()->format($rate['min_amount']) : '-' ?>
                                    </td>
                                    <td>
                                        <?= $rate['max_amount'] ? CurrencyFormatter::getInstance()->format($rate['max_amount']) : '-' ?>
                                    </td>
                                    <td><?= date('Y-m-d', strtotime($rate['effective_from'])) ?></td>
                                    <td>
                                        <?= $rate['effective_to'] ? date('Y-m-d', strtotime($rate['effective_to'])) : 'No End Date' ?>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-info"
                                                onclick="editRate(<?= htmlspecialchars(json_encode($rate)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Category Rates -->
        <div class="rates-section">
            <h3>Category Rates</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Rate %</th>
                            <th>Min Amount</th>
                            <th>Max Amount</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $categoryRates = array_filter($rates, function($rate) {
                            return $rate['type'] === 'category';
                        });
                        if (empty($categoryRates)): 
                        ?>
                            <tr>
                                <td colspan="7" class="text-center">No category rates defined</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categoryRates as $rate): ?>
                                <tr>
                                    <td><?= htmlspecialchars($categories[$rate['reference_id']]['name'] ?? 'Unknown') ?></td>
                                    <td><?= $rate['rate_percent'] ?>%</td>
                                    <td>
                                        <?= $rate['min_amount'] ? CurrencyFormatter::getInstance()->format($rate['min_amount']) : '-' ?>
                                    </td>
                                    <td>
                                        <?= $rate['max_amount'] ? CurrencyFormatter::getInstance()->format($rate['max_amount']) : '-' ?>
                                    </td>
                                    <td><?= date('Y-m-d', strtotime($rate['effective_from'])) ?></td>
                                    <td>
                                        <?= $rate['effective_to'] ? date('Y-m-d', strtotime($rate['effective_to'])) : 'No End Date' ?>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-info"
                                                onclick="editRate(<?= htmlspecialchars(json_encode($rate)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Product Rates -->
        <div class="rates-section">
            <h3>Product Rates</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Rate %</th>
                            <th>Min Amount</th>
                            <th>Max Amount</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $productRates = array_filter($rates, function($rate) {
                            return $rate['type'] === 'product';
                        });
                        if (empty($productRates)): 
                        ?>
                            <tr>
                                <td colspan="7" class="text-center">No product rates defined</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productRates as $rate): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($products[$rate['reference_id']]['name'] ?? 'Unknown') ?>
                                        <small class="text-muted d-block">
                                            <?= htmlspecialchars($products[$rate['reference_id']]['sku'] ?? '') ?>
                                        </small>
                                    </td>
                                    <td><?= $rate['rate_percent'] ?>%</td>
                                    <td>
                                        <?= $rate['min_amount'] ? CurrencyFormatter::getInstance()->format($rate['min_amount']) : '-' ?>
                                    </td>
                                    <td>
                                        <?= $rate['max_amount'] ? CurrencyFormatter::getInstance()->format($rate['max_amount']) : '-' ?>
                                    </td>
                                    <td><?= date('Y-m-d', strtotime($rate['effective_from'])) ?></td>
                                    <td>
                                        <?= $rate['effective_to'] ? date('Y-m-d', strtotime($rate['effective_to'])) : 'No End Date' ?>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-info"
                                                onclick="editRate(<?= htmlspecialchars(json_encode($rate)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Rate Modal -->
<div class="modal fade" id="rateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Commission Rate</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="rateForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="rateId" name="id">

                    <div class="form-group">
                        <label for="type">Rate Type</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="global">Global</option>
                            <option value="category">Category</option>
                            <option value="product">Product</option>
                        </select>
                    </div>

                    <div id="referenceSection" style="display: none;">
                        <div class="form-group" id="categorySelect" style="display: none;">
                            <label for="category_id">Category</label>
                            <select id="category_id" class="form-control">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" id="productSelect" style="display: none;">
                            <label for="product_id">Product</label>
                            <select id="product_id" class="form-control">
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id'] ?>">
                                        <?= htmlspecialchars($product['name']) ?> (<?= $product['sku'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" id="reference_id" name="reference_id">

                    <div class="form-group">
                        <label for="rate_percent">Commission Rate (%)</label>
                        <input type="number" 
                               id="rate_percent" 
                               name="rate_percent" 
                               class="form-control" 
                               required 
                               min="0" 
                               max="100" 
                               step="0.01">
                    </div>

                    <div class="form-group">
                        <label for="min_amount">Minimum Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" 
                                   id="min_amount" 
                                   name="min_amount" 
                                   class="form-control" 
                                   min="0" 
                                   step="0.01">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="max_amount">Maximum Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" 
                                   id="max_amount" 
                                   name="max_amount" 
                                   class="form-control" 
                                   min="0" 
                                   step="0.01">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="effective_from">Effective From</label>
                        <input type="date" 
                               id="effective_from" 
                               name="effective_from" 
                               class="form-control" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="effective_to">Effective To</label>
                        <input type="date" 
                               id="effective_to" 
                               name="effective_to" 
                               class="form-control">
                        <small class="form-text text-muted">
                            Leave empty for no end date
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Rate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.commission-rates-page {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.rates-sections {
    display: grid;
    gap: 20px;
}

.rates-section {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.rates-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.table {
    margin-bottom: 0;
}

.table th {
    background-color: #f8f9fa;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
    }

    .actions {
        width: 100%;
        display: flex;
        gap: 10px;
    }

    .actions .btn {
        flex: 1;
    }
}
</style>

<script>
function showRateModal() {
    resetRateForm();
    $('#rateModal').modal('show');
}

function editRate(rate) {
    resetRateForm();
    
    document.getElementById('rateId').value = rate.id;
    document.getElementById('type').value = rate.type;
    document.getElementById('rate_percent').value = rate.rate_percent;
    document.getElementById('min_amount').value = rate.min_amount || '';
    document.getElementById('max_amount').value = rate.max_amount || '';
    document.getElementById('effective_from').value = rate.effective_from.split(' ')[0];
    document.getElementById('effective_to').value = rate.effective_to ? rate.effective_to.split(' ')[0] : '';

    if (rate.type !== 'global') {
        document.getElementById('referenceSection').style.display = 'block';
        if (rate.type === 'category') {
            document.getElementById('categorySelect').style.display = 'block';
            document.getElementById('category_id').value = rate.reference_id;
        } else {
            document.getElementById('productSelect').style.display = 'block';
            document.getElementById('product_id').value = rate.reference_id;
        }
    }

    document.getElementById('reference_id').value = rate.reference_id;
    $('#rateModal').modal('show');
}

function resetRateForm() {
    document.getElementById('rateForm').reset();
    document.getElementById('rateId').value = '';
    document.getElementById('reference_id').value = '';
    document.getElementById('referenceSection').style.display = 'none';
    document.getElementById('categorySelect').style.display = 'none';
    document.getElementById('productSelect').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const referenceSection = document.getElementById('referenceSection');
    const categorySelect = document.getElementById('categorySelect');
    const productSelect = document.getElementById('productSelect');
    const referenceIdInput = document.getElementById('reference_id');

    typeSelect.addEventListener('change', function() {
        if (this.value === 'global') {
            referenceSection.style.display = 'none';
            categorySelect.style.display = 'none';
            productSelect.style.display = 'none';
            referenceIdInput.value = '';
        } else {
            referenceSection.style.display = 'block';
            if (this.value === 'category') {
                categorySelect.style.display = 'block';
                productSelect.style.display = 'none';
                document.getElementById('category_id').required = true;
                document.getElementById('product_id').required = false;
            } else {
                categorySelect.style.display = 'none';
                productSelect.style.display = 'block';
                document.getElementById('category_id').required = false;
                document.getElementById('product_id').required = true;
            }
        }
    });

    document.getElementById('category_id').addEventListener('change', function() {
        referenceIdInput.value = this.value;
    });

    document.getElementById('product_id').addEventListener('change', function() {
        referenceIdInput.value = this.value;
    });

    document.getElementById('rateForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const type = typeSelect.value;
        if (type !== 'global' && !referenceIdInput.value) {
            alert(`Please select a ${type === 'category' ? 'category' : 'product'}`);
            return;
        }

        const minAmount = parseFloat(document.getElementById('min_amount').value) || 0;
        const maxAmount = parseFloat(document.getElementById('max_amount').value) || 0;
        
        if (maxAmount > 0 && minAmount > maxAmount) {
            alert('Minimum amount cannot be greater than maximum amount');
            return;
        }

        const effectiveFrom = new Date(document.getElementById('effective_from').value);
        const effectiveTo = document.getElementById('effective_to').value ? 
                           new Date(document.getElementById('effective_to').value) : null;
        
        if (effectiveTo && effectiveFrom > effectiveTo) {
            alert('Effective from date cannot be later than effective to date');
            return;
        }

        this.submit();
    });
});
</script>
