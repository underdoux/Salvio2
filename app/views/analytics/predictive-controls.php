<!-- Prediction Controls -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Predictive Analytics</h5>
                <div class="row">
                    <div class="col-md-3">
                        <label for="predictionType" class="form-label">Prediction Model</label>
                        <select class="form-select" id="predictionType" onchange="updatePrediction()">
                            <option value="linear">Linear Regression</option>
                            <option value="exponential">Exponential Growth</option>
                            <option value="moving">Moving Average</option>
                            <option value="seasonal">Seasonal Forecast</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="forecastPeriod" class="form-label">Forecast Period</label>
                        <select class="form-select" id="forecastPeriod" onchange="updatePrediction()">
                            <option value="1">1 Month</option>
                            <option value="3">3 Months</option>
                            <option value="6">6 Months</option>
                            <option value="12">12 Months</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="confidenceLevel" class="form-label">Confidence Level</label>
                        <select class="form-select" id="confidenceLevel" onchange="updatePrediction()">
                            <option value="0.90">90%</option>
                            <option value="0.95" selected>95%</option>
                            <option value="0.99">99%</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="seasonalityPeriod" class="form-label">Seasonality Period</label>
                        <select class="form-select" id="seasonalityPeriod" onchange="updatePrediction()">
                            <option value="4">Quarterly</option>
                            <option value="12" selected>Monthly</option>
                            <option value="52">Weekly</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div id="predictionMetrics"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Prediction Results -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Forecast Results</h5>
                <div class="chart-container" style="position: relative; height:400px;">
                    <canvas id="forecastChart"></canvas>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Model Performance</h6>
                                <div id="modelMetrics"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Forecast Summary</h6>
                                <div id="forecastSummary"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Confidence Intervals</h6>
                                <div id="confidenceIntervals"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
