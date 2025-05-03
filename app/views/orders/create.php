<div class="create-order-page">
    <div class="page-header">
        <h2>Create New Order</h2>
        <div class="actions">
            <a href="<?= $baseUrl ?>/orders" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="orderForm" class="order-form">
        <div class="form-sections">
            <!-- Customer Information -->
            <div class="form-section">
                <h3>Customer Information</h3>
                
                <div class="form-group">
                    <label for="customer_name">Customer Name *</label>
                    <input type="text" 
                           id="customer_name" 
                           name="customer_name" 
                           class="form-control" 
                           required 
                           value="<?= htmlspecialchars($data['customer_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="customer_type">Customer Type *</label>
                    <select id="customer_type" name="customer_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="pharmacy" <?= ($data['customer_type'] ?? '') === 'pharmacy' ? 'selected' : '' ?>>
                            Pharmacy
                        </option>
                        <option value="clinic" <?= ($data['customer_type'] ?? '') === 'clinic' ? 'selected' : '' ?>>
                            Clinic
                        </option>
                        <option value="hospital" <?= ($data['customer_type'] ?? '') === 'hospital' ? 'selected' : '' ?>>
                            Hospital
                        </option>
                        <option value="other" <?= ($data['customer_type'] ?? '') === 'other' ? 'selected' : '' ?>>
                            Other
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="customer_phone">Phone Number</label>
                    <input type="tel" 
                           id="customer_phone" 
                           name="customer_phone" 
                           class="form-control"
                           value="<?= htmlspecialchars($data['customer_phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="customer_address">Address</label>
                    <textarea id="customer_address" 
                            name="customer_address" 
                            class="form-control" 
                            rows="3"><?= htmlspecialchars($data['customer_address'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Order Items -->
            <div class="form-section">
                <h3>Order Items</h3>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="orderItemsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th width="100">Quantity</th>
                                <th width="150">Unit Price</th>
                                <th width="100">Discount %</th>
                                <th width="150">Subtotal</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="no-items">
                                <td colspan="6" class="text-center">No items added</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOrderItem()">
                                        <i class="fas fa-plus"></i> Add Item
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="order-summary">
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th>Subtotal:</th>
                                    <td class="text-right" id="subtotalAmount">Rp 0</td>
                                </tr>
                                <tr>
                                    <th>
                                        Order Discount:
                                        <div class="input-group input-group-sm mt-1">
                                            <input type="number" 
                                                   name="discount_amount"
                                                   id="discountAmount"
                                                   class="form-control"
                                                   max="<?= $maxDiscountAmount ?>"
                                                   value="0" 
                                                   min="0">
                                        </div>
                                        <div class="input-group input-group-sm mt-1">
                                            <input type="text" 
                                                   name="discount_reason" 
                                                   id="discountReason" 
                                                   class="form-control" 
                                                   placeholder="Discount reason">
                                        </div>
                                    </th>
                                    <td class="text-right" id="discountDisplay">-Rp 0</td>
                                </tr>
                                <tr>
                                    <th>Total:</th>
                                    <td class="text-right" id="totalAmount">Rp 0</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="form-section">
                <h3>Payment Information</h3>
                
                <div class="form-group">
                    <label for="payment_type">Payment Type *</label>
                    <select id="payment_type" name="payment_type" class="form-control" required>
                        <option value="cash" <?= ($data['payment_type'] ?? '') === 'cash' ? 'selected' : '' ?>>
                            Cash
                        </option>
                        <option value="installment" <?= ($data['payment_type'] ?? '') === 'installment' ? 'selected' : '' ?>>
                            Installment
                        </option>
                    </select>
                </div>

                <div id="installmentFields" style="display: none;">
                    <div class="alert alert-info">
                        Payment schedule can be set up after creating the order
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="form-section">
                <h3>Shipping Information</h3>
                
                <div class="form-group">
                    <label for="shipping_method">Shipping Method</label>
                    <select id="shipping_method" name="shipping[method]" class="form-control">
                        <option value="">Select Method</option>
                        <option value="pickup">Customer Pickup</option>
                        <option value="delivery">Delivery Service</option>
                    </select>
                </div>

                <div id="shippingFields" style="display: none;">
                    <div class="form-group">
                        <label for="shipping_cost">Shipping Cost</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" 
                                   id="shipping_cost" 
                                   name="shipping[cost]" 
                                   class="form-control" 
                                   value="0" 
                                   min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="shipping_notes">Shipping Notes</label>
                        <textarea id="shipping_notes" 
                                name="shipping[notes]" 
                                class="form-control" 
                                rows="2"></textarea>
                    </div>
                </div>
            </div>

            <!-- Additional Notes -->
            <div class="form-section">
                <h3>Additional Notes</h3>
                
                <div class="form-group">
                    <textarea id="notes" 
                            name="notes" 
                            class="form-control" 
                            rows="3"><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Create Order
            </button>
            <a href="<?= $baseUrl ?>/orders" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<style>
.create-order-page {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.form-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.form-section {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.table {
    margin-bottom: 0;
}

.order-summary {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 20px 0;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .form-sections {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let products = <?= json_encode($products) ?>;
let orderItems = [];

function addOrderItem() {
    const tbody = document.querySelector('#orderItemsTable tbody');
    const noItemsRow = tbody.querySelector('.no-items');
    if (noItemsRow) {
        noItemsRow.remove();
    }

    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <select class="form-control" name="items[][product_id]" required onchange="updateProductPrice(this)">
                <option value="">Select Product</option>
                ${products.map(p => `
                    <option value="${p.id}" data-price="${p.selling_price}">
                        ${p.name} (${p.sku})
                    </option>
                `).join('')}
            </select>
        </td>
        <td>
            <input type="number" 
                   class="form-control" 
                   name="items[][quantity]" 
                   value="1" 
                   min="1" 
                   required 
                   onchange="updateSubtotal(this)">
        </td>
        <td>
            <input type="number" 
                   class="form-control" 
                   readonly 
                   value="0">
        </td>
        <td>
            <input type="number" 
                   class="form-control" 
                   name="items[][discount_percent]" 
                   value="0" 
                   min="0" 
                   max="100" 
                   onchange="updateSubtotal(this)">
            <input type="text" 
                   class="form-control mt-1" 
                   name="items[][discount_reason]" 
                   placeholder="Discount reason"
                   style="display: none;">
        </td>
        <td>
            <input type="number" 
                   class="form-control" 
                   readonly 
                   value="0">
        </td>
        <td>
            <button type="button" 
                    class="btn btn-sm btn-danger" 
                    onclick="removeOrderItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
}

function removeOrderItem(button) {
    const row = button.closest('tr');
    row.remove();

    const tbody = document.querySelector('#orderItemsTable tbody');
    if (tbody.children.length === 0) {
        tbody.innerHTML = `
            <tr class="no-items">
                <td colspan="6" class="text-center">No items added</td>
            </tr>
        `;
    }

    calculateTotals();
}

function updateProductPrice(select) {
    const row = select.closest('tr');
    const option = select.selectedOptions[0];
    const price = option.dataset.price;
    
    row.querySelector('td:nth-child(3) input').value = price;
    updateSubtotal(select);
}

function updateSubtotal(element) {
    const row = element.closest('tr');
    const quantity = parseFloat(row.querySelector('td:nth-child(2) input').value);
    const price = parseFloat(row.querySelector('td:nth-child(3) input').value);
    const discount = parseFloat(row.querySelector('td:nth-child(4) input').value);
    
    const subtotal = quantity * price * (1 - discount/100);
    row.querySelector('td:nth-child(5) input').value = subtotal.toFixed(2);

    const discountInput = row.querySelector('td:nth-child(4) input[type="text"]');
    if (discount > 0) {
        discountInput.style.display = 'block';
        discountInput.required = true;
    } else {
        discountInput.style.display = 'none';
        discountInput.required = false;
    }

    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('#orderItemsTable tbody tr:not(.no-items)').forEach(row => {
        subtotal += parseFloat(row.querySelector('td:nth-child(5) input').value);
    });

    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const total = subtotal - discount;

    document.getElementById('subtotalAmount').textContent = `Rp ${subtotal.toFixed(2)}`;
    document.getElementById('discountDisplay').textContent = `-Rp ${discount.toFixed(2)}`;
    document.getElementById('totalAmount').textContent = `Rp ${total.toFixed(2)}`;

    const discountReason = document.getElementById('discountReason');
    if (discount > 0) {
        discountReason.style.display = 'block';
        discountReason.required = true;
    } else {
        discountReason.style.display = 'none';
        discountReason.required = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize first item row
    addOrderItem();

    // Payment type change handler
    document.getElementById('payment_type').addEventListener('change', function() {
        const installmentFields = document.getElementById('installmentFields');
        installmentFields.style.display = this.value === 'installment' ? 'block' : 'none';
    });

    // Shipping method change handler
    document.getElementById('shipping_method').addEventListener('change', function() {
        const shippingFields = document.getElementById('shippingFields');
        shippingFields.style.display = this.value === 'delivery' ? 'block' : 'none';
    });

    // Discount amount change handler
    document.getElementById('discountAmount').addEventListener('input', calculateTotals);

    // Form submission handler
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        const items = document.querySelectorAll('#orderItemsTable tbody tr:not(.no-items)');
        if (items.length === 0) {
            e.preventDefault();
            alert('Please add at least one item to the order');
            return;
        }

        // Validate discounts
        let hasInvalidDiscount = false;
        items.forEach(row => {
            const discount = parseFloat(row.querySelector('td:nth-child(4) input[type="number"]').value);
            const reason = row.querySelector('td:nth-child(4) input[type="text"]').value;
            if (discount > 0 && !reason) {
                hasInvalidDiscount = true;
            }
        });

        const orderDiscount = parseFloat(document.getElementById('discountAmount').value);
        const orderDiscountReason = document.getElementById('discountReason').value;
        if (orderDiscount > 0 && !orderDiscountReason) {
            hasInvalidDiscount = true;
        }

        if (hasInvalidDiscount) {
            e.preventDefault();
            alert('Please provide a reason for all discounts');
            return;
        }
    });
});
</script>
