<!DOCTYPE html>
<html>
<head>
    <title>Dashboard IoT Minimalis</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <script src="assets/js/jquery-3.4.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .dashboard-header {
            margin-bottom: 20px;
            text-align: center;
        }
        .chart-panel {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px;
            margin-bottom: 20px;
            height: 300px;
        }
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        canvas {
            display: block !important;
            width: 100% !important;
            height: calc(100% - 30px) !important;
        }
        #loading {
            text-align: center;
            padding: 10px;
            color: #666;
            font-size: 14px;
        }
        .value-display {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }
        .suhu-value { color: #4285F4; }
        .kelembaban-value { color: #EA4335; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="dashboard-header">
            <h4>Monitoring Suhu & Kelembaban</h4>
            <small class="text-muted">Data realtime (5 pembacaan terakhir)</small>
        </div>
        
        <div id="loading">Memuat data...</div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="value-display suhu-value" id="currentSuhu">- °C</div>
                <div class="chart-panel">
                    <div class="chart-title">GRAFIK SUHU (°C)</div>
                    <canvas id="chartSuhu"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="value-display kelembaban-value" id="currentKelembaban">- %</div>
                <div class="chart-panel">
                    <div class="chart-title">GRAFIK KELEMBABAN (%)</div>
                    <canvas id="chartKelembaban"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inisialisasi chart
        let suhuChart, kelembabanChart;
        
        $(document).ready(function() {
            initCharts();
            updateCharts();
            setInterval(updateCharts, 1000);
        });

        function initCharts() {
            const suhuCtx = document.getElementById('chartSuhu').getContext('2d');
            const kelembabanCtx = document.getElementById('chartKelembaban').getContext('2d');
            
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y.toFixed(1)}${context.dataset.label.includes('Suhu') ? '°C' : '%'}`;
                            }
                        }
                    }
                },
                animation: { duration: 0 },
                scales: {
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: { 
                        grid: { color: '#f5f5f5' },
                        ticks: { font: { size: 10 } }
                    }
                }
            };

            suhuChart = new Chart(suhuCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Suhu',
                        data: [],
                        borderColor: '#4285F4',
                        backgroundColor: 'rgba(66, 133, 244, 0.1)',
                        tension: 0.3,
                        fill: true,
                        borderWidth: 2
                    }]
                },
                options: commonOptions
            });

            kelembabanChart = new Chart(kelembabanCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Kelembaban',
                        data: [],
                        borderColor: '#EA4335',
                        backgroundColor: 'rgba(234, 67, 53, 0.1)',
                        tension: 0.3,
                        fill: true,
                        borderWidth: 2
                    }]
                },
                options: commonOptions
            });
        }

        function updateCharts() {
            $.ajax({
                url: 'data.php',
                dataType: 'json',
                success: function(data) {
                    if(data.status === 'success') {
                        // Update suhu chart
                        suhuChart.data.labels = data.data.labels;
                        suhuChart.data.datasets[0].data = data.data.suhu;
                        suhuChart.options.scales.y.min = Math.min(...data.data.suhu) - 2;
                        suhuChart.options.scales.y.max = Math.max(...data.data.suhu) + 2;
                        suhuChart.update();
                        
                        // Update kelembaban chart
                        kelembabanChart.data.labels = data.data.labels;
                        kelembabanChart.data.datasets[0].data = data.data.kelembaban;
                        kelembabanChart.update();
                        
                        // Update current values display
                        $('#currentSuhu').text(data.data.suhu[data.data.suhu.length-1].toFixed(1) + ' °C');
                        $('#currentKelembaban').text(data.data.kelembaban[data.data.kelembaban.length-1].toFixed(1) + ' %');
                        
                        $('#loading').hide();
                    } else {
                        $('#loading').text('Error: ' + data.error).css('color', 'red');
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading').text('Gagal memuat data: ' + error).css('color', 'red');
                }
            });
        }
    </script>
</body>
</html>