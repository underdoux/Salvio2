<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Commission Rates</h1>
        <a href="/Salvio2/public/commissions" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Commissions
        </a>
    </div>

    <!-- Add New Rate -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Add New Commission Rate</h5>
        </div>
        <div class="card-body">
            <form action="/Salvio2/public/commissions/updateRate" method="POST" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type" required onchange="handleTypeChange()">
                        <option value="global">Global</option>
                        <option value="category">Category</option>
                        <option value="product">Product</option>
                    </select>
                </div>

                <div class="col-md-3" id="categorySelect" style="display: none;">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="reference_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3" id="productSelect" style="display: none;">
                    <label for="product_id" class="form-label">Product</label>
                    <select class="form-select" id="product_id" name="reference_id">
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?= $product['id'] ?>">
                            <?= htmlspecialchars($product['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="rate" class="form-label">Rate (%)</label>
                    <input type="number" class="form-control" id="rate" name="rate" 
                           required min="0" max="100" step="0.01">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Add Rate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Current Rates -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Current Commission Rates</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Rate (%)</th>
                            <th>Last Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rates)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No commission rates defined</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($rates as $rate): ?>
                        <tr>
                            <td><?= ucfirst($rate['type']) ?></td>
                            <td><?= htmlspecialchars($rate['reference_name']) ?></td>
                            <td><?= number_format($rate['rate'], 2) ?>%</td>
                            <td><?= date('Y-m-d H:i', strtotime($rate['updated_at'])) ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRateModal"
                                        data-rate-id="<?= $rate['id'] ?>"
                                        data-rate="<?= $rate['rate'] ?>">
                                    <i class="fas fa-edit"></i> Edit
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

<!-- Edit Rate Modal -->
<div class="modal fade" id="editRateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/Salvio2/public/commissions/updateRate" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Commission Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="rate_id" id="editRateId">
                    <div class="mb-3">
                        <label for="editRate" class="form-label">Rate (%)</label>
                        <input type="number" class="form-control" id="editRate" name="rate"
                               required min="0" max="100" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Rate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handleTypeChange() {
    const type = document.getElementById('type').value;
    const categorySelect = document.getElementById('categorySelect');
    const productSelect = document.getElementById('productSelect');
    
    categorySelect.style.display = 'none';
    productSelect.style.display = 'none';
    
    if (type === 'category') {
        categorySelect.style.display = 'block';
        document.getElementById('category_id').required = true;
        document.getElementById('product_id').required = false;
    } else if (type === 'product') {
        productSelect.style.display = 'block';
        document.getElementById('product_id').required = true;
        document.getElementById('category_id').required = false;
    } else {
        document.getElementById('category_id').required = false;
        document.getElementById('product_id').required = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle edit rate modal
    const editRateModal = document.getElementById('editRateModal');
    if (editRateModal) {
        editRateModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const rateId = button.getAttribute('data-rate-id');
            const rate = button.getAttribute('data-rate');
            
            editRateModal.querySelector('#editRateId').value = rateId;
            editRateModal.querySelector('#editRate').value = rate;
        });
    }
});
</script>
