<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detection Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5 fw-bold"><i class="bi bi-search"></i> Detection Logs</h1>
            </div>
        </div>

        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4">
            <div class="container-fluid">
                <div class="navbar-nav">
                    <a class="nav-link" href="{{ route('admin.bot-protection.index') }}">Dashboard</a>
                    <a class="nav-link active" href="{{ route('admin.bot-protection.logs') }}">Detection Logs</a>
                    <a class="nav-link" href="{{ route('admin.bot-protection.blocked-ips') }}">Blocked IPs</a>
                    <a class="nav-link" href="{{ route('admin.bot-protection.whitelist') }}">Whitelist</a>
                </div>
            </div>
        </nav>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip" class="form-control" placeholder="192.168.1.1" value="{{ request('ip') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Action</label>
                        <select name="action" class="form-select">
                            <option value="">All Actions</option>
                            <option value="logged" {{ request('action') === 'logged' ? 'selected' : '' }}>Logged</option>
                            <option value="challenged" {{ request('action') === 'challenged' ? 'selected' : '' }}>Challenged</option>
                            <option value="blocked" {{ request('action') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Min Risk Score</label>
                        <input type="number" name="min_risk" class="form-control" placeholder="50" value="{{ request('min_risk') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>URL</th>
                                <th>Risk Score</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td class="text-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td><code>{{ $log->ip_address }}</code></td>
                                    <td style="max-width: 200px;" class="text-truncate" title="{{ $log->user_agent }}">
                                        {{ Str::limit($log->user_agent, 30) }}
                                    </td>
                                    <td style="max-width: 250px;" class="text-truncate" title="{{ $log->request_url }}">
                                        {{ Str::limit($log->request_url, 40) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $log->risk_score >= 70 ? 'danger' : ($log->risk_score >= 50 ? 'warning' : 'success') }}">
                                            {{ $log->risk_score }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($log->detection_reason, 40) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $log->action_taken === 'blocked' ? 'danger' : ($log->action_taken === 'challenged' ? 'warning' : 'info') }}">
                                            {{ $log->action_taken }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-5">No logs found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($logs->hasPages())
                <div class="card-footer bg-white">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
