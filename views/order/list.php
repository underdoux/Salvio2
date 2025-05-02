<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Order List - POS Pharma</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-gray-100 p-6">
    <div id="app" class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Order List</h1>
        <div class="mb-4 flex justify-between items-center">
            <input v-model="search" type="text" placeholder="Search orders..." class="border rounded px-3 py-2 w-1/3" />
            <button @click="goToCreate" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create Order</button>
        </div>
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-4 py-2 text-left">Order ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">User ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Total</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Payment Type</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Created At</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-gray-100">
                    <td class="border border-gray-300 px-4 py-2">{{ order.id }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ order.user_id }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ order.status }}</td>
                    <td class="border border-gray-300 px-4 py-2">${{ order.total.toFixed(2) }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ order.payment_type }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ order.created_at }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button @click="viewOrder(order.id)" class="text-blue-600 hover:underline mr-2">View</button>
                        <button @click="editOrder(order.id)" class="text-green-600 hover:underline mr-2">Edit</button>
                        <button @click="deleteOrder(order.id)" class="text-red-600 hover:underline">Delete</button>
                    </td>
                </tr>
                <tr v-if="filteredOrders.length === 0">
                    <td colspan="7" class="text-center py-4">No orders found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    search: '',
                    orders: <?php
                        $ordersArray = [];
                        while ($row = $orders->fetch(PDO::FETCH_ASSOC)) {
                            $ordersArray[] = $row;
                        }
                        echo json_encode($ordersArray);
                    ?>
                };
            },
            computed: {
                filteredOrders() {
                    if (!this.search) return this.orders;
                    return this.orders.filter(o =>
                        o.id.toString().includes(this.search) ||
                        o.status.toLowerCase().includes(this.search.toLowerCase()) ||
                        o.payment_type.toLowerCase().includes(this.search.toLowerCase())
                    );
                }
            },
            methods: {
                goToCreate() {
                    window.location.href = '/orders/create';
                },
                viewOrder(id) {
                    window.location.href = `/orders/view/${id}`;
                },
                editOrder(id) {
                    window.location.href = `/orders/edit/${id}`;
                },
                deleteOrder(id) {
                    if (confirm('Are you sure you want to delete this order?')) {
                        fetch(`/orders/delete/${id}`, { method: 'POST' })
                            .then(() => window.location.reload())
                            .catch(err => alert('Failed to delete order.'));
                    }
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
