<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Order - POS Pharma</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-gray-100 p-6">
    <div id="app" class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Create Order</h1>
        <form @submit.prevent="submitOrder" class="space-y-4">
            <div>
                <label for="user_id" class="block font-semibold mb-1">User ID</label>
                <input v-model="order.user_id" type="number" id="user_id" class="border rounded px-3 py-2 w-full" required />
            </div>
            <div>
                <label for="tax_percentage" class="block font-semibold mb-1">Tax Percentage</label>
                <input v-model="order.tax_percentage" type="number" step="0.01" id="tax_percentage" class="border rounded px-3 py-2 w-full" />
            </div>
            <div>
                <label for="payment_type" class="block font-semibold mb-1">Payment Type</label>
                <select v-model="order.payment_type" id="payment_type" class="border rounded px-3 py-2 w-full">
                    <option value="cash">Cash</option>
                    <option value="installment">Installment</option>
                </select>
            </div>

            <h2 class="text-xl font-semibold mt-6 mb-2">Order Items</h2>
            <table class="w-full border-collapse border border-gray-300 mb-4">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-gray-300 px-4 py-2">Product</th>
                        <th class="border border-gray-300 px-4 py-2">Quantity</th>
                        <th class="border border-gray-300 px-4 py-2">Original Price</th>
                        <th class="border border-gray-300 px-4 py-2">Adjusted Price</th>
                        <th class="border border-gray-300 px-4 py-2">Adjustment Reason</th>
                        <th class="border border-gray-300 px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in order.items" :key="index" class="hover:bg-gray-100">
                        <td class="border border-gray-300 px-4 py-2">
                            <select v-model="item.product_id" class="w-full border rounded px-2 py-1" required>
                                <option value="" disabled>Select product</option>
                                <option v-for="product in products" :value="product.id">{{ product.name }}</option>
                            </select>
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <input v-model.number="item.quantity" type="number" min="1" class="w-full border rounded px-2 py-1" required />
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <input v-model.number="item.original_price" type="number" step="0.01" class="w-full border rounded px-2 py-1" required />
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <input v-model.number="item.adjusted_price" type="number" step="0.01" class="w-full border rounded px-2 py-1" />
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <input v-model="item.adjustment_reason" type="text" class="w-full border rounded px-2 py-1" />
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <button type="button" @click="removeItem(index)" class="text-red-600 hover:underline">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <button type="button" @click="addItem" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Item</button>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">Create Order</button>
            </div>
        </form>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    order: {
                        user_id: null,
                        tax_percentage: 0,
                        payment_type: 'cash',
                        items: []
                    },
                    products: <?php
                        $productsArray = [];
                        while ($row = $products->fetch(PDO::FETCH_ASSOC)) {
                            $productsArray[] = $row;
                        }
                        echo json_encode($productsArray);
                    ?>
                };
            },
            methods: {
                addItem() {
                    this.order.items.push({
                        product_id: '',
                        quantity: 1,
                        original_price: 0.0,
                        adjusted_price: null,
                        adjustment_reason: ''
                    });
                },
                removeItem(index) {
                    this.order.items.splice(index, 1);
                },
                submitOrder() {
                    // Prepare form data for submission
                    const formData = new FormData();
                    formData.append('user_id', this.order.user_id);
                    formData.append('tax_percentage', this.order.tax_percentage);
                    formData.append('payment_type', this.order.payment_type);
                    formData.append('total', this.calculateTotal());

                    formData.append('items', JSON.stringify(this.order.items));

                    fetch('/orders/create', {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        if (response.ok) {
                            alert('Order created successfully!');
                            window.location.href = '/orders';
                        } else {
                            alert('Failed to create order.');
                        }
                    }).catch(() => {
                        alert('Failed to create order.');
                    });
                },
                calculateTotal() {
                    let total = 0;
                    this.order.items.forEach(item => {
                        total += (item.adjusted_price !== null && item.adjusted_price !== '') ? item.adjusted_price * item.quantity : item.original_price * item.quantity;
                    });
                    return total;
                }
            },
            mounted() {
                this.addItem();
            }
        }).mount('#app');
    </script>
</body>
</html>
