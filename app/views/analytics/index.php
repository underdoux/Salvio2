<div class="container mt-4">
    <h1><?php echo $title; ?></h1>
    <p class="lead"><?php echo $description; ?></p>

    <div class="row mt-4">
        <!-- Best Selling Products Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Best Selling Products</h5>
                    <canvas id="bestSellingChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Market Response Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Market Response by Customer Type</h5>
                    <canvas id="marketResponseChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Sales Trends Chart -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sales Trends (Last 6 Months)</h5>
                    <canvas id="salesTrendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Best Selling Products Chart
const bestSellingData = <?php echo json_encode($best_selling); ?>;
new Chart(document.getElementById('bestSellingChart'), {
    type: 'bar',
    data: {
        labels: bestSellingData.map(item => item.name),
        datasets: [{
            label: 'Units Sold',
            data: bestSellingData.map(item => item.total_quantity),
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Market Response Chart
const marketResponseData = <?php echo json_encode($market_response); ?>;
new Chart(document.getElementById('marketResponseChart'), {
    type: 'pie',
    data: {
        labels: marketResponseData.map(item => item.customer_type),
        datasets: [{
            data: marketResponseData.map(item => item.total_revenue),
            backgroundColor: [
                'rgba(255, 99, 132, 0.5)',
                'rgba(54, 162, 235, 0.5)',
                'rgba(255, 206, 86, 0.5)',
                'rgba(75, 192, 192, 0.5)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true
    }
});

// Sales Trends Chart
const salesTrendsData = <?php echo json_encode($sales_trends); ?>;
new Chart(document.getElementById('salesTrendsChart'), {
    type: 'line',
    data: {
        labels: salesTrendsData.map(item => item.month),
        datasets: [{
            label: 'Revenue',
            data: salesTrendsData.map(item => item.total_revenue),
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
