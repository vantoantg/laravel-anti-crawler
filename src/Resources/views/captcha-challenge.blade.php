<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Required</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-card {
            max-width: 500px;
            width: 100%;
        }
        .icon-large {
            font-size: 64px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg verification-card">
                    <div class="card-body p-5 text-center">
                        <div class="icon-large mb-4">🛡️</div>
                        <h1 class="h3 mb-3">Verification Required</h1>
                        <p class="text-muted mb-4">
                            We've detected unusual activity from your connection. Please complete the verification below to continue.
                        </p>

                        <div class="alert alert-warning" role="alert">
                            <strong>Reason:</strong> {{ $reason }}<br>
                            <strong>Risk Score:</strong> {{ $risk_score }}/100
                        </div>

                        <form method="POST" action="{{ $return_url }}">
                            @csrf
                            <input type="hidden" name="captcha_token" value="{{ $token }}">
                            
                            <div class="d-flex justify-content-center my-4">
                                {!! $captcha_widget !!}
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                Verify and Continue
                            </button>
                        </form>

                        <p class="text-muted small mt-4 mb-0">
                            If you believe this is an error, please contact support.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
