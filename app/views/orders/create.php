<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Create Order</h1>
        <a href="/Salvio2/public/orders" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>

    <form method="POST" action="/Salvio2/public/orders/create" id="orderForm">
        <div class="row">
            <!-- Order Details -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <div id="orderItems">
                            <!-- Dynamic items will be added here -->
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="addItem">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Customer ID</label>
                            <input type="text" class="form-control" id="customer_id" name="customer_id" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_type" class="form-label">Payment Type</label>
                            <select class="form-select" id="payment_type" name="payment_type" required>
                                <option value="cash">Cash</option>
                                <option value="installment">Installment</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="discount" class="form-label">Discount (%)</label>
                            <input type="number" class="form-control" id="discount" name="discount" 
                                   min="0" max="30" step="0.01">
                        </div>

                        <div class="mb-3">
                            <label for="discount_reason" class="form-label">Discount Reason</label>
                            <textarea class="form-control" id="discount_reason" name="discount_reason" 
                                    rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subtotal</label>
                            <div class="h5">₱<span id="subtotal">0.00</span></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Discount Amount</label>
                            <div class="h5">₱<span id="discountAmount">0.00</span></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Total Amount</label>
                            <div class="h4">₱<span id="totalAmount">0.00</span></div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Create Order</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Item Template -->
<template id="itemTemplate">
    <div class="order-item border rounded p-3 mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Product</label>
                <select class="form-select product-select" name="items[{index}][product_id]" required>
                    <option value="">Select Product</option>
                    <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>" 
                            data-price="<?= $product['selling_price'] ?>"
                            data-stock="<?= $product['stock_type'] === 'stocked' ? $product['current_stock'] : 'unlimited' ?>">
                        <?= htmlspecialchars($product['name']) ?> - ₱<?= number_format($product['selling_price'], 2) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control quantity-input" 
                       name="items[{index}][quantity]" min="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Price</label>
                <div class="h5">₱<span class="item-price">0.00</span></div>
            </div>
            <div class="col-md-1">
                <label class="form-label d-block">&nbsp;</label>
                <button type="button" class="btn btn-danger remove-item">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderItems = document.getElementById('orderItems');
    const addItemBtn = document.getElementById('addItem');
    const itemTemplate = document.getElementById('itemTemplate');
    let itemCount = 0;

    // Add initial item
    addItem();

    // Add item button click handler
    addItemBtn.addEventListener('click', addItem);

    // Form submission handler
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        if (orderItems.children.length === 0) {
            e.preventDefault();
            alert('Please add at least one item to the order');
        }
    });

    // Discount input handler
    document.getElementById('discount').addEventListener('input', calculateTotals);

    function addItem() {
        const itemHtml = itemTemplate.innerHTML.replace(/{index}/g, itemCount++);
        const itemWrapper = document.createElement('div');
        itemWrapper.innerHTML = itemHtml;
        const itemElement = itemWrapper.firstElementChild;

        // Add event listeners
        itemElement.querySelector('.product-select').addEventListener('change', updateItemPrice);
        itemElement.querySelector('.quantity-input').addEventListener('input', updateItemPrice);
        itemElement.querySelector('.remove-item').addEventListener('click', function() {
            itemElement.remove();
            calculateTotals();
        });

        orderItems.appendChild(itemElement);
    }

    function updateItemPrice(e) {
        const item = e.target.closest('.order-item');
        const select = item.querySelector('.product-select');
        const quantity = item.querySelector('.quantity-input').value;
        const option = select.selectedOptions[0];

        if (option && option.dataset.price) {
            const price = parseFloat(option.dataset.price);
            const total = price * quantity;
            item.querySelector('.item-price').textContent = total.toFixed(2);
            calculateTotals();
        }
    }

    function calculateTotals() {
        let subtotal = 0;
        document.querySelectorAll('.item-price').forEach(price => {
            subtotal += parseFloat(price.textContent || 0);
        });

        const discount = parseFloat(document.getElementById('discount').value || 0);
        const discountAmount = subtotal * (discount / 100);
        const total = subtotal - discountAmount;

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('discountAmount').textContent = discountAmount.toFixed(2);
        document.getElementById('totalAmount').textContent = total.toFixed(2);
    }
});
</script>
