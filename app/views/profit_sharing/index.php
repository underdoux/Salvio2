<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><?php echo $title; ?></h1>
            <p class="lead mb-0"><?php echo $description; ?></p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#calculateProfitModal">
                <i class="fas fa-calculator"></i> Calculate Monthly Profit
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All</option>
                        <option value="draft" <?= isset($_GET['status']) && $_GET['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="final" <?= isset($_GET['status']) && $_GET['status'] === 'final' ? 'selected' : '' ?>>Final</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year">
                        <?php 
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 2; $y--) {
                            $selected = isset($_GET['year']) && $_GET['year'] == $y ? 'selected' : '';
                            echo "<option value=\"{$y}\" {$selected}>{$y}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="/Salvio2/public/profit-sharing" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Monthly Profits Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th class="text-end">Total Sales</th>
                            <th class="text-end">Total Product Cost</th>
                            <th class="text-end">Total Commissions</th>
                            <th class="text-end">Total Expenses</th>
                            <th class="text-end">Net Profit</th>
                            <th class="text-center">Distributions</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($profits)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No profit records found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($profits as $profit): ?>
                        <tr>
                            <td><?= date('F Y', strtotime($profit['period'])) ?></td>
                            <td class="text-end">₱<?= number_format($profit['total_sales'], 2) ?></td>
                            <td class="text-end">₱<?= number_format($profit['total_product_cost'], 2) ?></td>
                            <td class="text-end">₱<?= number_format($profit['total_commissions'], 2) ?></td>
                            <td class="text-end">₱<?= number_format($profit['total_expenses'], 2) ?></td>
                            <td class="text-end">₱<?= number_format($profit['net_profit'], 2) ?></td>
                            <td class="text-center">
                                <?= $profit['total_distributions'] ?> 
                                (₱<?= number_format($profit['total_distributed'], 2) ?>)
                            </td>
                            <td>
                                <span class="badge bg-<?= $profit['status_class'] ?>">
                                    <?= ucfirst($profit['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="/Salvio2/public/profit-sharing/view/<?= $profit['id'] ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($profit['status'] === 'draft'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-success"
                                            data-bs-toggle="modal"
                                            data-bs-target="#finalizeModal"
                                            data-profit-id="<?= $profit['id'] ?>">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <a href="/Salvio2/public/profit-sharing/export/<?= $profit['id'] ?>" 
                                       class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
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

<!-- Calculate Monthly Profit Modal -->
<div class="modal fade" id="calculateProfitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/Salvio2/public/profit-sharing/calculate" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Calculate Monthly Profit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="month" class="form-label">Period</label>
                        <input type="month" class="form-control" id="month" name="period" 
                               value="<?= date('Y-m') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Calculate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Finalize Profit Modal -->
<div class="modal fade" id="finalizeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/Salvio2/public/profit-sharing/finalize" method="POST">
                <input type="hidden" name="profit_id" id="profitId">
                <div class="modal-header">
                    <h5 class="modal-title">Finalize Monthly Profit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to finalize this monthly profit?</p>
                    <p class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This action cannot be undone. Once finalized, the profit calculations and distributions cannot be modified.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Finalize</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle finalize modal
    const finalizeModal = document.getElementById('finalizeModal');
    if (finalizeModal) {
        finalizeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const profitId = button.getAttribute('data-profit-id');
            finalizeModal.querySelector('#profitId').value = profitId;
        });
    }
});
</script>
