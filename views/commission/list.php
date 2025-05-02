<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Commission List - POS Pharma</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-gray-100 p-6">
    <div id="app" class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Commission List</h1>
        <div class="mb-4 flex justify-between items-center">
            <input v-model="search" type="text" placeholder="Search commissions..." class="border rounded px-3 py-2 w-1/3" />
            <button @click="goToAdd" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Commission</button>
        </div>
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-4 py-2 text-left">User ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Product ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Category ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Rate (%)</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Min Cap</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Max Cap</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="commission in filteredCommissions" :key="commission.id" class="hover:bg-gray-100">
                    <td class="border border-gray-300 px-4 py-2">{{ commission.user_id }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ commission.product_id || 'N/A' }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ commission.category_id || 'N/A' }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ commission.rate.toFixed(2) }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ commission.min_cap || 'N/A' }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ commission.max_cap || 'N/A' }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button @click="editCommission(commission.id)" class="text-blue-600 hover:underline mr-2">Edit</button>
                        <button @click="deleteCommission(commission.id)" class="text-red-600 hover:underline">Delete</button>
                    </td>
                </tr>
                <tr v-if="filteredCommissions.length === 0">
                    <td colspan="7" class="text-center py-4">No commissions found.</td>
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
                    commissions: <?php
                        $commissionsArray = [];
                        while ($row = $commissions->fetch(PDO::FETCH_ASSOC)) {
                            $commissionsArray[] = $row;
                        }
                        echo json_encode($commissionsArray);
                    ?>
                };
            },
            computed: {
                filteredCommissions() {
                    if (!this.search) return this.commissions;
                    return this.commissions.filter(c =>
                        c.user_id.toString().includes(this.search) ||
                        (c.product_id && c.product_id.toString().includes(this.search)) ||
                        (c.category_id && c.category_id.toString().includes(this.search))
                    );
                }
            },
            methods: {
                goToAdd() {
                    window.location.href = '/commissions/add';
                },
                editCommission(id) {
                    window.location.href = `/commissions/edit/${id}`;
                },
                deleteCommission(id) {
                    if (confirm('Are you sure you want to delete this commission?')) {
                        fetch(`/commissions/delete/${id}`, { method: 'POST' })
                            .then(() => window.location.reload())
                            .catch(err => alert('Failed to delete commission.'));
                    }
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
