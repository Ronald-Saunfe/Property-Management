<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Monitoring Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .slow-request { background-color: #ffdddd; }
        .medium-request { background-color: #ffffdd; }
        .fast-request { background-color: #ddffdd; }
        .metrics-card { height: 100%; }
        .chart-container { height: 300px; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="mb-4">Performance Monitoring Dashboard</h1>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card metrics-card">
                    <div class="card-body">
                        <h5 class="card-title">Average Response Time</h5>
                        <h2 class="display-4" id="avg-response-time">--</h2>
                        <p class="text-muted">milliseconds</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metrics-card">
                    <div class="card-body">
                        <h5 class="card-title">Average Query Count</h5>
                        <h2 class="display-4" id="avg-query-count">--</h2>
                        <p class="text-muted">queries per request</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metrics-card">
                    <div class="card-body">
                        <h5 class="card-title">Average Query Time</h5>
                        <h2 class="display-4" id="avg-query-time">--</h2>
                        <p class="text-muted">milliseconds</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metrics-card">
                    <div class="card-body">
                        <h5 class="card-title">Average Memory Usage</h5>
                        <h2 class="display-4" id="avg-memory-usage">--</h2>
                        <p class="text-muted">MB</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Response Time Trend</h5>
                    </div>
                    <div class="card-body chart-container">
                        <canvas id="response-time-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Query Count Trend</h5>
                    </div>
                    <div class="card-body chart-container">
                        <canvas id="query-count-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Requests</h5>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" id="refresh-btn">Refresh</button>
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Filter</button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#" data-filter="all">All Requests</a>
                        <a class="dropdown-item" href="#" data-filter="slow">Slow Requests</a>
                        <a class="dropdown-item" href="#" data-filter="high-query">High Query Count</a>
                        <a class="dropdown-item" href="#" data-filter="high-memory">High Memory Usage</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Endpoint</th>
                                <th>Execution Time</th>
                                <th>Query Count</th>
                                <th>Query Time</th>
                                <th>Memory Usage</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requests-table-body">
                            @if(isset($logs) && count($logs) > 0)
                                @foreach($logs as $log)
                                    @php
                                        // Parse the log entry (simple parsing for demonstration)
                                        $logData = json_decode($log, true);
                                        if (!$logData || !isset($logData['context'])) continue;
                                        
                                        $metrics = $logData['context'];
                                        $executionTime = isset($metrics['execution_time']) ? str_replace('ms', '', $metrics['execution_time']) : 0;
                                        $queryCount = $metrics['query_count'] ?? 0;
                                        $queryTime = isset($metrics['query_time']) ? str_replace('ms', '', $metrics['query_time']) : 0;
                                        $memoryUsage = isset($metrics['memory_usage']) ? str_replace('MB', '', $metrics['memory_usage']) : 0;
                                        
                                        // Determine row class based on execution time
                                        $rowClass = '';
                                        if ($executionTime > 500) $rowClass = 'slow-request';
                                        else if ($executionTime > 200) $rowClass = 'medium-request';
                                        else $rowClass = 'fast-request';
                                    @endphp
                                    
                                    <tr class="{{ $rowClass }}">
                                        <td>{{ $metrics['timestamp'] ?? $logData['datetime'] ?? 'N/A' }}</td>
                                        <td>{{ $metrics['endpoint'] ?? 'N/A' }}</td>
                                        <td>{{ $metrics['execution_time'] ?? 'N/A' }}</td>
                                        <td>{{ $metrics['query_count'] ?? 'N/A' }}</td>
                                        <td>{{ $metrics['query_time'] ?? 'N/A' }}</td>
                                        <td>{{ $metrics['memory_usage'] ?? 'N/A' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detailsModal" data-log='{{ $log }}'>Details</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center">No performance logs available</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Endpoint</h6>
                        <p id="modal-endpoint"></p>
                    </div>
                    <div class="mb-3">
                        <h6>Performance Metrics</h6>
                        <div class="row">
                            <div class="col-md-3"><strong>Execution Time:</strong> <span id="modal-execution-time"></span></div>
                            <div class="col-md-3"><strong>Query Count:</strong> <span id="modal-query-count"></span></div>
                            <div class="col-md-3"><strong>Query Time:</strong> <span id="modal-query-time"></span></div>
                            <div class="col-md-3"><strong>Memory Usage:</strong> <span id="modal-memory-usage"></span></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6>Slow Queries</h6>
                        <div id="modal-slow-queries">
                            <p class="text-muted">No slow queries recorded</p>
                        </div>
                    </div>
                    <div>
                        <h6>Raw Log</h6>
                        <pre id="modal-raw-log" class="bg-light p-3" style="max-height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sample data for charts (would be populated from actual logs in production)
        const sampleDates = Array.from({length: 7}, (_, i) => {
            const date = new Date();
            date.setDate(date.getDate() - i);
            return date.toLocaleDateString();
        }).reverse();
        
        const sampleResponseTimes = [120, 145, 135, 180, 160, 175, 130];
        const sampleQueryCounts = [25, 30, 28, 35, 32, 34, 27];
        
        // Initialize charts
        const responseTimeChart = new Chart(
            document.getElementById('response-time-chart'),
            {
                type: 'line',
                data: {
                    labels: sampleDates,
                    datasets: [{
                        label: 'Avg Response Time (ms)',
                        data: sampleResponseTimes,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            }
        );
        
        const queryCountChart = new Chart(
            document.getElementById('query-count-chart'),
            {
                type: 'line',
                data: {
                    labels: sampleDates,
                    datasets: [{
                        label: 'Avg Query Count',
                        data: sampleQueryCounts,
                        borderColor: 'rgb(153, 102, 255)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            }
        );
        
        // Calculate averages from sample data
        document.getElementById('avg-response-time').textContent = 
            Math.round(sampleResponseTimes.reduce((a, b) => a + b, 0) / sampleResponseTimes.length);
            
        document.getElementById('avg-query-count').textContent = 
            Math.round(sampleQueryCounts.reduce((a, b) => a + b, 0) / sampleQueryCounts.length);
            
        document.getElementById('avg-query-time').textContent = '45';
        document.getElementById('avg-memory-usage').textContent = '24.5';
        
        // Modal functionality
        const detailsModal = document.getElementById('detailsModal');
        detailsModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const logData = JSON.parse(button.getAttribute('data-log'));
            const metrics = logData.context || {};
            
            document.getElementById('modal-endpoint').textContent = metrics.endpoint || 'N/A';
            document.getElementById('modal-execution-time').textContent = metrics.execution_time || 'N/A';
            document.getElementById('modal-query-count').textContent = metrics.query_count || 'N/A';
            document.getElementById('modal-query-time').textContent = metrics.query_time || 'N/A';
            document.getElementById('modal-memory-usage').textContent = metrics.memory_usage || 'N/A';
            document.getElementById('modal-raw-log').textContent = JSON.stringify(logData, null, 2);
            
            const slowQueriesContainer = document.getElementById('modal-slow-queries');
            if (metrics.slow_queries && metrics.slow_queries.length > 0) {
                let html = '<div class="list-group">';
                metrics.slow_queries.forEach(query => {
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Query Time: ${query.time}</h6>
                            </div>
                            <p class="mb-1">${query.sql}</p>
                            <small>Bindings: ${JSON.stringify(query.bindings)}</small>
                        </div>
                    `;
                });
                html += '</div>';
                slowQueriesContainer.innerHTML = html;
            } else {
                slowQueriesContainer.innerHTML = '<p class="text-muted">No slow queries recorded</p>';
            }
        });
        
        // Refresh button functionality
        document.getElementById('refresh-btn').addEventListener('click', () => {
            window.location.reload();
        });
    </script>
</body>
</html>