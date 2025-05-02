<?php require_once '../app/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Commission Details - <?= htmlspecialchars($username) ?></h1>
        <a href="/Salvio2/public/commissions" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Commissions
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th class="text-end">Commission Amount</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($commissions)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No commission records found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($commissions as $commission): ?>
                        <tr>
                            <td>
                                <a href="/Salvio2/public/orders/view/<?= $commission['order_id'] ?>" 
                                   class="text-decoration-none">
                                    #<?= $commission['order_id'] ?>
                                </a>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($commission['order_date'])) ?></td>
                            <td class="text-end">₱<?= number_format($commission['amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $this->getStatusBadgeClass($commission['status']) ?>">
                                    <?= ucfirst($commission['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($commission['status'] !== 'paid'): ?>
                                <button type="button" class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#updateStatusModal"
                                        data-commission-id="<?= $commission['id'] ?>"
                                        data-current-status="<?= $commission['status'] ?>">
                                    <i class="fas fa-edit"></i> Update Status
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <!-- Summary Row -->
                        <tr class="table-info">
                            <td colspan="2"><strong>Total</strong></td>
                            <td class="text-end">
                                <strong>₱<?= number_format(array_sum(array_column($commissions, 'amount')), 2) ?></strong>
                            </td>
                            <td colspan="2"></td>
                        </tr>
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
