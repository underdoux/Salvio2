<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Product List - POS Pharma</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-gray-100 p-6">
    <div id="app" class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Product List</h1>
        <div class="mb-4 flex justify-between items-center">
            <input v-model="search" type="text" placeholder="Search products..." class="border rounded px-3 py-2 w-1/3" />
            <button @click="goToAdd" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Product</button>
        </div>
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-4 py-2 text-left">Name</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Category</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Stock</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">By Order</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Price</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-gray-100">
                    <td class="border border-gray-300 px-4 py-2">{{ product.name }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ product.category_name || 'Uncategorized' }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ product.stock }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ product.by_order ? 'Yes' : 'No' }}</td>
                    <td class="border border-gray-300 px-4 py-2">${{ product.price.toFixed(2) }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button @click="editProduct(product.id)" class="text-blue-600 hover:underline mr-2">Edit</button>
                        <button @click="deleteProduct(product.id)" class="text-red-600 hover:underline">Delete</button>
                    </td>
                </tr>
                <tr v-if="filteredProducts.length === 0">
                    <td colspan="6" class="text-center py-4">No products found.</td>
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
                    products: <?php
                        $productsArray = [];
                        while ($row = $products->fetch(PDO::FETCH_ASSOC)) {
                            $productsArray[] = $row;
                        }
                        echo json_encode($productsArray);
                    ?>
                };
            },
            computed: {
                filteredProducts() {
                    if (!this.search) return this.products;
                    return this.products.filter(p =>
                        p.name.toLowerCase().includes(this.search.toLowerCase()) ||
                        (p.category_name && p.category_name.toLowerCase().includes(this.search.toLowerCase()))
                    );
                }
            },
            methods: {
                goToAdd() {
                    window.location.href = '/products/add';
                },
                editProduct(id) {
                    window.location.href = `/products/edit/${id}`;
                },
                deleteProduct(id) {
                    if (confirm('Are you sure you want to delete this product?')) {
                        fetch(`/products/delete/${id}`, { method: 'POST' })
                            .then(() => window.location.reload())
                            .catch(err => alert('Failed to delete product.'));
                    }
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
