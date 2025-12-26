<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocked IPs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5 fw-bold"><i class="bi bi-slash-circle"></i> Blocked IPs</h1>
            </div>
        </div>

        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4">
            <div class="container-fluid">
                <div class="navbar-nav">
                    <a class="nav-link" href="{{ route('admin.bot-protection.index') }}">Dashboard</a>
                    <a class="nav-link" href="{{ route('admin.bot-protection.logs') }}">Detection Logs</a>
                    <a class="nav-link active" href="{{ route('admin.bot-protection.blocked-ips') }}">Blocked IPs</a>
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

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Block New IP</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.bot-protection.block-ip') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">IP Address</label>
                            <input type="text" name="ip_address" class="form-control" required placeholder="192.168.1.1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duration (minutes, empty = permanent)</label>
                            <input type="number" name="duration" class="form-control" placeholder="60">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-ban"></i> Block IP
                            </button>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" required rows="2" placeholder="Reason for blocking..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Currently Blocked IPs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>IP Address</th>
                                <th>Reason</th>
                                <th>Blocked By</th>
                                <th>Expires</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($blockedIps as $blocked)
                                <tr>
                                    <td><code>{{ $blocked->ip_address }}</code></td>
                                    <td>{{ Str::limit($blocked->reason, 50) }}</td>
                                    <td><span class="badge bg-{{ $blocked->blocked_by === 'auto' ? 'warning' : 'danger' }}">{{ $blocked->blocked_by }}</span></td>
                                    <td>
                                        @if($blocked->expires_at)
                                            {{ $blocked->expires_at->diffForHumans() }}
                                        @else
                                            <span class="badge bg-danger">Permanent</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $blocked->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.bot-protection.unblock-ip', $blocked->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Unblock this IP?')">
                                                <i class="bi bi-check-circle"></i> Unblock
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-5">No blocked IPs</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($blockedIps->hasPages())
                <div class="card-footer bg-white">
                    {{ $blockedIps->links() }}
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
