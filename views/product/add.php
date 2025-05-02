<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Product - POS Pharma</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-md mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-6">Add New Product</h1>
        <form method="POST" action="/products/add" class="space-y-4">
            <div>
                <label for="name" class="block text-gray-700">Product Name</label>
                <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label for="stock" class="block text-gray-700">Stock Quantity</label>
                <input type="number" id="stock" name="stock" min="0" value="0" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="by_order" name="by_order" class="mr-2" />
                <label for="by_order" class="text-gray-700">By Order (Non-stocked)</label>
            </div>
            <div>
                <label for="price" class="block text-gray-700">Price</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Add Product</button>
        </form>
        <a href="/products" class="inline-block mt-4 text-blue-600 hover:underline">Back to Product List</a>
    </div>
</body>
</html>
