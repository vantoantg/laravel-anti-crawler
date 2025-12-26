<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Whitelist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5 fw-bold"><i class="bi bi-check-circle"></i> IP Whitelist</h1>
            </div>
        </div>

        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4">
            <div class="container-fluid">
                <div class="navbar-nav">
                    <a class="nav-link" href="{{ route('admin.bot-protection.index') }}">Dashboard</a>
                    <a class="nav-link" href="{{ route('admin.bot-protection.logs') }}">Detection Logs</a>
                    <a class="nav-link" href="{{ route('admin.bot-protection.blocked-ips') }}">Blocked IPs</a>
                    <a class="nav-link active" href="{{ route('admin.bot-protection.whitelist') }}">Whitelist</a>
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
                <h5 class="mb-0">Add IP to Whitelist</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.bot-protection.whitelist.add') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">IP Address</label>
                            <input type="text" name="ip_address" class="form-control" required placeholder="192.168.1.1">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Description (optional)</label>
                            <input type="text" name="description" class="form-control" placeholder="e.g., Office Network, Google Bot">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-plus-circle"></i> Add to Whitelist
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Whitelisted IPs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>IP Address</th>
                                <th>Description</th>
                                <th>Added</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($whitelist as $item)
                                <tr>
                                    <td><code>{{ $item->ip_address }}</code></td>
                                    <td>{{ $item->description ?? '-' }}</td>
                                    <td class="text-nowrap">{{ $item->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.bot-protection.whitelist.remove', $item->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove from whitelist?')">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-5">No whitelisted IPs</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($whitelist->hasPages())
                <div class="card-footer bg-white">
                    {{ $whitelist->links() }}
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
