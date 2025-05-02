<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Insights Dashboard - POS Pharma</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body class="bg-gray-100 p-6">
    <div id="app" class="max-w-7xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-3xl font-bold mb-6">Insights Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-50 p-4 rounded shadow">
                <h2 class="text-xl font-semibold mb-4">Best Selling Products</h2>
                <canvas id="bestSellingChart"></canvas>
            </div>
            <div class="bg-gray-50 p-4 rounded shadow">
                <h2 class="text-xl font-semibold mb-4">Market Response by Customer Type</h2>
                <canvas id="marketResponseChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        const { createApp, onMounted } = Vue;

        createApp({
            data() {
                return {
                    bestSellingData: {
                        labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
                        datasets: [{
                            label: 'Units Sold',
                            data: [120, 90, 75, 60, 45],
                            backgroundColor: 'rgba(59, 130, 246, 0.7)'
                        }]
                    },
                    marketResponseData: {
                        labels: ['Clinic', 'Pharmacy', 'Hospital', 'Online'],
                        datasets: [{
                            label: 'Responses',
                            data: [50, 30, 15, 5],
                            backgroundColor: [
                                'rgba(16, 185, 129, 0.7)',
                                'rgba(234, 179, 8, 0.7)',
                                'rgba(239, 68, 68, 0.7)',
                                'rgba(59, 130, 246, 0.7)'
                            ]
                        }]
                    }
                };
            },
            methods: {
                renderCharts() {
                    const ctx1 = document.getElementById('bestSellingChart').getContext('2d');
                    new Chart(ctx1, {
                        type: 'bar',
                        data: this.bestSellingData,
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });

                    const ctx2 = document.getElementById('marketResponseChart').getContext('2d');
                    new Chart(ctx2, {
                        type: 'doughnut',
                        data: this.marketResponseData,
                        options: {
                            responsive: true
                        }
                    });
                }
            },
            mounted() {
                this.renderCharts();
            }
        }).mount('#app');
    </script>
</body>
</html>
