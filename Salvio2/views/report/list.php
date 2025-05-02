<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Report List - POS Pharma</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-gray-100 p-6">
    <div id="app" class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Report List</h1>
        <div class="mb-4 flex justify-between items-center">
            <input v-model="search" type="text" placeholder="Search reports..." class="border rounded px-3 py-2 w-1/3" />
            <button @click="goToAdd" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Report</button>
        </div>
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-4 py-2 text-left">Type</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Generated At</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="report in filteredReports" :key="report.id" class="hover:bg-gray-100">
                    <td class="border border-gray-300 px-4 py-2">{{ report.type }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ report.generated_at }}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button @click="viewReport(report.id)" class="text-blue-600 hover:underline mr-2">View</button>
                        <button @click="deleteReport(report.id)" class="text-red-600 hover:underline">Delete</button>
                    </td>
                </tr>
                <tr v-if="filteredReports.length === 0">
                    <td colspan="3" class="text-center py-4">No reports found.</td>
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
                    reports: <?php
                        $reportsArray = [];
                        while ($row = $reports->fetch(PDO::FETCH_ASSOC)) {
                            $reportsArray[] = $row;
                        }
                        echo json_encode($reportsArray);
                    ?>
                };
            },
            computed: {
                filteredReports() {
                    if (!this.search) return this.reports;
                    return this.reports.filter(r =>
                        r.type.toLowerCase().includes(this.search.toLowerCase())
                    );
                }
            },
            methods: {
                goToAdd() {
                    window.location.href = '/reports/add';
                },
                viewReport(id) {
                    window.location.href = `/reports/view/${id}`;
                },
                deleteReport(id) {
                    if (confirm('Are you sure you want to delete this report?')) {
                        fetch(`/reports/delete/${id}`, { method: 'POST' })
                            .then(() => window.location.reload())
                            .catch(err => alert('Failed to delete report.'));
                    }
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
