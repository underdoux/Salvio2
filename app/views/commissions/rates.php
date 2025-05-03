<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><?php echo $title; ?></h1>
            <p class="lead"><?php echo $description; ?></p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRateModal">
            Add New Rate
        </button>
    </div>

    <!-- Commission Rates Table -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Target</th>
                            <th>Rate (%)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rates as $rate): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rate['rate_type']); ?></td>
                                <td><?php echo htmlspecialchars($rate['target_name']); ?></td>
                                <td><?php echo number_format($rate['rate'], 2); ?>%</td>
                                <td>
                                    <span class="badge bg-<?php echo $rate['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($rate['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editRate(<?php echo $rate['id']; ?>)">
                                        Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteRate(<?php echo $rate['id']; ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Rate Modal -->
<div class="modal fade" id="addRateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Commission Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRateForm">
                    <div class="mb-3">
                        <label class="form-label">Rate Type</label>
                        <select class="form-select" name="rate_type" onchange="updateTargetSelect()">
                            <option value="global">Global</option>
                            <option value="category">Category</option>
                            <option value="product">Product</option>
                        </select>
                    </div>

                    <div class="mb-3" id="categorySelectGroup" style="display: none;">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="productSelectGroup" style="display: none;">
                        <label class="form-label">Product</label>
                        <select class="form-select" name="product_id">
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                    (<?php echo htmlspecialchars($product['category_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Commission Rate (%)</label>
                        <input type="number" class="form-control" name="rate" 
                               min="0" max="100" step="0.01" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveRate()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function updateTargetSelect() {
    const rateType = document.querySelector('select[name="rate_type"]').value;
    document.getElementById('categorySelectGroup').style.display = rateType === 'category' ? 'block' : 'none';
    document.getElementById('productSelectGroup').style.display = rateType === 'product' ? 'block' : 'none';
}

function saveRate() {
    const form = document.getElementById('addRateForm');
    const formData = new FormData(form);
    
    fetch('/Salvio2/public/commissions/save-rate', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error saving commission rate');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving commission rate');
    });
}

function editRate(id) {
    fetch(`/Salvio2/public/commissions/get-rate/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate form with rate data
                const form = document.getElementById('addRateForm');
                form.querySelector('[name="rate"]').value = data.rate.rate;
                form.querySelector('[name="rate_type"]').value = data.rate.rate_type.toLowerCase();
                updateTargetSelect();
                
                if (data.rate.category_id) {
                    form.querySelector('[name="category_id"]').value = data.rate.category_id;
                }
                if (data.rate.product_id) {
                    form.querySelector('[name="product_id"]').value = data.rate.product_id;
                }
                
                // Show modal
                new bootstrap.Modal(document.getElementById('addRateModal')).show();
            } else {
                alert(data.message || 'Error loading commission rate');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading commission rate');
        });
}

function deleteRate(id) {
    if (confirm('Are you sure you want to delete this commission rate?')) {
        fetch(`/Salvio2/public/commissions/delete-rate/${id}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error deleting commission rate');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting commission rate');
            });
    }
}
</script>
