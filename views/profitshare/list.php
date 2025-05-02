<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profit Sharing List - POS Pharma</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-gray-100 p-6">
    <div id="app" class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Profit Sharing List</h1>
        <div class="mb-4 flex justify-between items-center">
            <input v-model="search" type="text" placeholder="Search profit shares..." class="border rounded px-3 py-2 w-1/3" />
            <button @click="goToAdd" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Profit Share</button>
        </div>
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-4 py-2 text-left">Investor ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Capital Percentage (%)</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="profitshare in filteredProfitShares" :key="profitshare.id" class="hover:bg-gray-100">
                    <td class="border border-gray-300 px-4 py-2">{{ profitshare.investor_id }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ profitshare.capital_percentage.toFixed(2) }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button @click="editProfitShare(profitshare.id)" class="text-blue-600 hover:underline mr-2">Edit</button>
                        <button @click="deleteProfitShare(profitshare.id)" class="text-red-600 hover:underline">Delete</button>
                    </td>
                </tr>
                <tr v-if="filteredProfitShares.length === 0">
                    <td colspan="3" class="text-center py-4">No profit shares found.</td>
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
                    profitShares: <?php
                        $profitSharesArray = [];
                        while ($row = $profitShares->fetch(PDO::FETCH_ASSOC)) {
                            $profitSharesArray[] = $row;
                        }
                        echo json_encode($profitSharesArray);
                    ?>
                };
            },
            computed: {
                filteredProfitShares() {
                    if (!this.search) return this.profitShares;
                    return this.profitShares.filter(p =>
                        p.investor_id.toString().includes(this.search)
                    );
                }
            },
            methods: {
                goToAdd() {
                    window.location.href = '/profitshare/add';
                },
                editProfitShare(id) {
                    window.location.href = `/profitshare/edit/${id}`;
                },
                deleteProfitShare(id) {
                    if (confirm('Are you sure you want to delete this profit share?')) {
                        fetch(`/profitshare/delete/${id}`, { method: 'POST' })
                            .then(() => window.location.reload())
                            .catch(err => alert('Failed to delete profit share.'));
                    }
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
