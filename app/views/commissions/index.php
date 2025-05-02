<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Sales Commissions</h1>
        <a href="/Salvio2/public/commissions/rates" class="btn btn-primary">
            <i class="fas fa-cog"></i> Manage Rates
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All</option>
                        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                           value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="/Salvio2/public/commissions" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Commission Summary Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sales Person</th>
                            <th class="text-center">Total Orders</th>
                            <th class="text-end">Total Commission</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($summary)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No commission records found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($summary as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td class="text-center"><?= $row['total_orders'] ?></td>
                            <td class="text-end">₱<?= number_format($row['total_commission'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $this->getStatusBadgeClass($row['status']) ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="/Salvio2/public/commissions/details/<?= $row['user_id'] ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Details
                                </a>
                                <?php if ($row['status'] !== 'paid'): ?>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#updateStatusModal"
                                        data-commission-id="<?= $row['id'] ?>"
                                        data-current-status="<?= $row['status'] ?>">
                                    <i class="fas fa-edit"></i> Update Status
                                </button>
                                <?php endif; ?>
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

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/Salvio2/public/commissions/updateStatus" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Update Commission Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="commission_id" id="commissionId">
                    <div class="mb-3">
                        <label for="status" class="form-label">New Status</label>
                        <select class="form-select" id="modalStatus" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle status update modal
    const updateStatusModal = document.getElementById('updateStatusModal');
    if (updateStatusModal) {
        updateStatusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const commissionId = button.getAttribute('data-commission-id');
            const currentStatus = button.getAttribute('data-current-status');
            
            updateStatusModal.querySelector('#commissionId').value = commissionId;
            updateStatusModal.querySelector('#modalStatus').value = currentStatus;
        });
    }
});
</script>

<?php require_once '../app/views/layouts/footer.php'; ?>
