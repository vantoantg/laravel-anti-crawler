<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot Protection Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5 fw-bold"><i class="bi bi-shield-check"></i> Bot Protection Dashboard</h1>
            </div>
        </div>

        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4">
            <div class="container-fluid">
                <div class="navbar-nav">
                    <a class="nav-link active" href="{{ route('admin.bot-protection.index') }}">Dashboard</a>
                    <a class="nav-link" href="{{ route('admin.bot-protection.logs') }}">Detection Logs</a>
                    <a class="nav-link" href="{{ route('admin.bot-protection.blocked-ips') }}">Blocked IPs</a>
                    <a class="nav-link" href="{{ route('admin.bot-protection.whitelist') }}">Whitelist</a>
                </div>
            </div>
        </nav>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4 mb-4">
            <div class="col-md-4 col-lg-2">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Logs</h6>
                        <h2 class="card-title mb-0">{{ number_format($stats['total_logs']) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card shadow-sm border-danger">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Blocked Today</h6>
                        <h2 class="card-title mb-0 text-danger">{{ number_format($stats['blocked_today']) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card shadow-sm border-warning">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">High Risk Today</h6>
                        <h2 class="card-title mb-0 text-warning">{{ number_format($stats['high_risk_today']) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Active Blocks</h6>
                        <h2 class="card-title mb-0">{{ number_format($stats['active_blocks']) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card shadow-sm border-success">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Whitelisted IPs</h6>
                        <h2 class="card-title mb-0 text-success">{{ number_format($stats['whitelisted_ips']) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">CAPTCHA Success</h6>
                        <h2 class="card-title mb-0">{{ $stats['captcha_success_rate'] }}%</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>IP Address</th>
                                <th>Risk Score</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at->diffForHumans() }}</td>
                                    <td><code>{{ $log->ip_address }}</code></td>
                                    <td>
                                        <span class="badge bg-{{ $log->risk_score >= 70 ? 'danger' : ($log->risk_score >= 50 ? 'warning' : 'success') }}">
                                            {{ $log->risk_score }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($log->detection_reason, 50) }}</td>
                                    <td><span class="badge bg-{{ $log->action_taken === 'blocked' ? 'danger' : 'warning' }}">{{ $log->action_taken }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No recent activity</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Top Offenders (Last 7 Days)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>IP Address</th>
                                <th>Request Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topOffenders as $offender)
                                <tr>
                                    <td><code>{{ $offender->ip_address }}</code></td>
                                    <td>{{ number_format($offender->count) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
