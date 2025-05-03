// Predictive Analytics Functions

function generateForecast(data, type, periods) {
    switch(type) {
        case 'linear':
            return generateLinearForecast(data, periods);
        case 'exponential':
            return generateExponentialForecast(data, periods);
        case 'moving':
            return generateMovingAverageForecast(data, periods);
        case 'seasonal':
            return generateSeasonalForecast(data, periods);
        default:
            return generateLinearForecast(data, periods);
    }
}

function generateLinearForecast(data, periods) {
    const n = data.length;
    const x = Array.from({length: n}, (_, i) => i);
    const y = data;

    // Calculate linear regression
    const {slope, intercept} = calculateLinearRegression(x, y);
    
    // Generate predictions
    const predictions = [];
    const upper = [];
    const lower = [];
    const mse = calculateMSE(y, x.map(x => slope * x + intercept));
    const confidenceInterval = 1.96 * Math.sqrt(mse); // 95% confidence interval

    for (let i = 1; i <= periods; i++) {
        const forecast = slope * (n + i - 1) + intercept;
        predictions.push(forecast);
        upper.push(forecast + confidenceInterval);
        lower.push(forecast - confidenceInterval);
    }

    return {
        predictions,
        confidenceIntervals: { upper, lower },
        metrics: {
            r2: calculateR2(y, x.map(x => slope * x + intercept)),
            mse: mse,
            slope: slope
        }
    };
}

function generateExponentialForecast(data, periods) {
    // Take natural log of data to convert to linear form
    const logY = data.map(y => Math.log(Math.max(y, 0.01))); // Avoid log(0)
    const x = Array.from({length: data.length}, (_, i) => i);
    
    // Calculate linear regression on log-transformed data
    const {slope, intercept} = calculateLinearRegression(x, logY);
    
    // Generate predictions
    const predictions = [];
    const upper = [];
    const lower = [];
    const n = data.length;
    
    // Calculate error metrics on original scale
    const fitted = x.map(x => Math.exp(slope * x + intercept));
    const mse = calculateMSE(data, fitted);
    const confidenceInterval = 1.96 * Math.sqrt(mse);

    for (let i = 1; i <= periods; i++) {
        const forecast = Math.exp(slope * (n + i - 1) + intercept);
        predictions.push(forecast);
        upper.push(forecast * (1 + confidenceInterval));
        lower.push(forecast * (1 - confidenceInterval));
    }

    return {
        predictions,
        confidenceIntervals: { upper, lower },
        metrics: {
            growthRate: (Math.exp(slope) - 1) * 100,
            mse: mse,
            r2: calculateR2(data, fitted)
        }
    };
}

function generateMovingAverageForecast(data, periods) {
    const windowSize = 3; // Use last 3 periods for moving average
    const lastValues = data.slice(-windowSize);
    const ma = lastValues.reduce((a, b) => a + b) / windowSize;
    
    // Calculate standard deviation for confidence intervals
    const std = calculateStandardDeviation(lastValues);
    const confidenceInterval = 1.96 * std;

    const predictions = Array(periods).fill(ma);
    const upper = Array(periods).fill(ma + confidenceInterval);
    const lower = Array(periods).fill(ma - confidenceInterval);

    return {
        predictions,
        confidenceIntervals: { upper, lower },
        metrics: {
            lastAverage: ma,
            std: std,
            reliability: calculateReliability(data, windowSize)
        }
    };
}

function generateSeasonalForecast(data, periods) {
    const seasonLength = Math.min(12, Math.floor(data.length / 2)); // Adaptive season length
    const trends = detectSeasonalTrends(data, seasonLength);
    const predictions = [];
    const upper = [];
    const lower = [];
    
    for (let i = 1; i <= periods; i++) {
        const seasonIndex = (data.length + i - 1) % seasonLength;
        const trend = trends[seasonIndex];
        const forecast = trend.base * (1 + trend.seasonal);
        predictions.push(forecast);
        upper.push(forecast * (1 + trend.confidence));
        lower.push(forecast * (1 - trend.confidence));
    }

    return {
        predictions,
        confidenceIntervals: { upper, lower },
        metrics: {
            seasonalityStrength: calculateSeasonalityStrength(trends),
            reliability: calculateSeasonalReliability(data, seasonLength),
            dominantPeriod: findDominantPeriod(data)
        }
    };
}

// Statistical Helper Functions
function calculateLinearRegression(x, y) {
    const n = x.length;
    const sumX = x.reduce((a, b) => a + b, 0);
    const sumY = y.reduce((a, b) => a + b, 0);
    const sumXY = x.reduce((sum, xi, i) => sum + xi * y[i], 0);
    const sumXX = x.reduce((sum, xi) => sum + xi * xi, 0);

    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;

    return { slope, intercept };
}

function calculateMSE(actual, predicted) {
    return actual.reduce((sum, yi, i) => sum + Math.pow(yi - predicted[i], 2), 0) / actual.length;
}

function calculateR2(actual, predicted) {
    const mean = actual.reduce((a, b) => a + b, 0) / actual.length;
    const totalSS = actual.reduce((sum, yi) => sum + Math.pow(yi - mean, 2), 0);
    const residualSS = actual.reduce((sum, yi, i) => sum + Math.pow(yi - predicted[i], 2), 0);
    return 1 - (residualSS / totalSS);
}

function calculateStandardDeviation(data) {
    const mean = data.reduce((a, b) => a + b, 0) / data.length;
    const squaredDiffs = data.map(x => Math.pow(x - mean, 2));
    return Math.sqrt(squaredDiffs.reduce((a, b) => a + b, 0) / data.length);
}

function calculateReliability(data, windowSize) {
    const changes = [];
    for (let i = windowSize; i < data.length; i++) {
        const actual = data[i];
        const predicted = data.slice(i - windowSize, i).reduce((a, b) => a + b, 0) / windowSize;
        changes.push(Math.abs((actual - predicted) / predicted));
    }
    return (1 - changes.reduce((a, b) => a + b, 0) / changes.length) * 100;
}

function detectSeasonalTrends(data, seasonLength) {
    const trends = [];
    for (let i = 0; i < seasonLength; i++) {
        const seasonalValues = [];
        for (let j = i; j < data.length; j += seasonLength) {
            seasonalValues.push(data[j]);
        }
        const mean = seasonalValues.reduce((a, b) => a + b, 0) / seasonalValues.length;
        const std = calculateStandardDeviation(seasonalValues);
        trends.push({
            base: mean,
            seasonal: (mean - data.reduce((a, b) => a + b, 0) / data.length) / mean,
            confidence: 1.96 * std / mean
        });
    }
    return trends;
}

function calculateSeasonalityStrength(trends) {
    return trends.reduce((sum, trend) => sum + Math.abs(trend.seasonal), 0) / trends.length * 100;
}

function calculateSeasonalReliability(data, seasonLength) {
    const trends = detectSeasonalTrends(data, seasonLength);
    const predicted = [];
    for (let i = 0; i < data.length; i++) {
        const trend = trends[i % seasonLength];
        predicted.push(trend.base * (1 + trend.seasonal));
    }
    return (1 - calculateMSE(data, predicted) / calculateVariance(data)) * 100;
}

function calculateVariance(data) {
    const mean = data.reduce((a, b) => a + b, 0) / data.length;
    return data.reduce((sum, x) => sum + Math.pow(x - mean, 2), 0) / data.length;
}

function findDominantPeriod(data) {
    const n = data.length;
    let maxCorr = 0;
    let dominantPeriod = 1;
    
    for (let period = 2; period <= Math.floor(n/2); period++) {
        const corr = calculateAutocorrelation(data, period);
        if (corr > maxCorr) {
            maxCorr = corr;
            dominantPeriod = period;
        }
    }
    
    return dominantPeriod;
}

function calculateAutocorrelation(data, lag) {
    const n = data.length;
    const mean = data.reduce((a, b) => a + b, 0) / n;
    let numerator = 0;
    let denominator = 0;
    
    for (let i = 0; i < n - lag; i++) {
        numerator += (data[i] - mean) * (data[i + lag] - mean);
    }
    
    for (let i = 0; i < n; i++) {
        denominator += Math.pow(data[i] - mean, 2);
    }
    
    return numerator / denominator;
}
