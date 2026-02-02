<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sunrise Family Mart | Premier B2B Grocery Distribution</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Leading B2B grocery supply chain partner for Bangalore, Ramanagara, and Nelamangala.">

    @php($icon = Helpers::get_business_settings('fav_icon'))
    @php($logoName = Helpers::get_business_settings('logo'))
    @php($logo = Helpers::onErrorImage(
        $logoName,
        asset('storage/app/public/restaurant') . '/' . $logoName,
        asset('public/assets/admin/img/160x160/Zone99.png'),
        'restaurant/'
    ))

    <link rel="icon" href="{{ asset('storage/app/public/restaurant/' . $icon ?? '') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/vendor.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/icon-set/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/theme.minc619.css?v=1.0') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .hero-section {
            background: linear-gradient(135deg, #7A3E0F 0%, #a85819 100%);
            color: white;
            padding: 80px 0;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
        }
        .feature-card {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            height: 100%;
            padding: 2rem;
        }
        .feature-card:hover { transform: translateY(-5px); }
        .feature-icon { font-size: 2rem; color: #7A3E0F; margin-bottom: 1rem; }
        .btn-custom-primary {
            background-color: #7A3E0F;
            border-color: #7A3E0F;
            color: #fff;
            padding: 10px 25px;
        }
        .btn-custom-primary:hover { background-color: #5e2f0b; color: #fff; }
        .footer-link { color: #6c757d; text-decoration: none; font-size: 0.8rem; }
        .footer-link:hover { color: #fff; }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="{{ $logo }}" height="50" alt="Logo" class="me-2">
            <div>
                <span class="d-block fw-bold text-dark" style="line-height: 1;">Sunrise Family Mart</span>
                <span class="text-muted" style="font-size: 0.75rem;">B2B Supply Chain Solutions</span>
            </div>
        </a>

        <div class="ms-auto">
            <a href="https://play.google.com/store/apps/details?id=com.sunrisefamilymart.app"
               target="_blank"
               class="btn btn-success fw-medium px-4">
                Download App
            </a>
        </div>
    </div>
</nav>

<div style="height: 80px;"></div>

<section class="hero-section">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Streamlining Grocery Distribution</h1>
        <p class="lead mb-4">
            We empower retailers, hotels, and institutions with a reliable,
            technology-driven supply chain across Bangalore, Ramanagara, and Nelamangala.
        </p>
        <a href="#features" class="btn btn-light fw-bold px-4 py-2">Learn More</a>
    </div>
</section>

<section class="py-5" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <h6 class="text-uppercase text-muted fw-bold">Why Partner With Us</h6>
            <h2 class="fw-bold">Optimized for Your Business Growth</h2>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h4 class="fw-bold">Quality Assurance</h4>
                    <p class="text-muted">
                        Strict quality checks, hygienic storage, and batch-level tracking ensure freshness.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">📉</div>
                    <h4 class="fw-bold">Transparent Pricing</h4>
                    <p class="text-muted">
                        Predictable B2B pricing with no hidden costs to protect retailer margins.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">🚚</div>
                    <h4 class="fw-bold">Reliable Logistics</h4>
                    <p class="text-muted">
                        Efficient delivery across urban, semi-urban, and rural locations.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="bg-dark text-white pt-5 pb-3">
    <div class="container">
        <div class="row">
            <div class="col-md-6 mb-4">
                <h5 class="fw-bold">Sunrise Family Mart</h5>
                <p class="text-muted small">
                    Your trusted partner in B2B grocery distribution.
                </p>
            </div>
            <div class="col-md-6 mb-4 text-md-end">
                <h6 class="fw-bold">Contact Us</h6>
                <p class="mb-1">📧 admin@sunrisefamilymart.com</p>
                <p class="text-muted small">Bangalore, Karnataka, India</p>
            </div>
        </div>

        <hr style="border-color: rgba(255,255,255,0.1);">

        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                &copy; {{ date('Y') }} Sunrise Family Mart. All rights reserved.
            </small>

            <a href="javascript:"
               class="footer-link"
               data-bs-toggle="offcanvas"
               data-bs-target="#adminLogin">
                Authorized Access Only
            </a>
        </div>
    </div>
</footer>

<!-- ADMIN LOGIN (NO CAPTCHA) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="adminLogin" style="width: 300px;">
    <div class="position-absolute top-0 end-0 p-3">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column justify-content-center px-3">
        <form action="{{ route('admin.auth.login') }}" method="post">
            @csrf

            <div class="mb-3 text-center">
                <img src="{{ $logo }}" height="40" class="mb-2">
                <p class="text-muted small fw-bold">{{ translate('Admin Access') }}</p>
            </div>

            <div class="mb-2">
                <label class="small text-muted">{{ translate('Email') }}</label>
                <input type="email" class="form-control form-control-sm" name="email" required>
            </div>

            <div class="mb-2">
                <label class="small text-muted">{{ translate('Password') }}</label>
                <input type="password" class="form-control form-control-sm" name="password" required>
            </div>

            <div class="mb-3 d-flex align-items-center">
                <input type="checkbox" name="remember" class="form-check-input me-2">
                <label class="small">{{ translate('Remember me') }}</label>
            </div>

            <button class="btn btn-custom-primary btn-sm w-100 fw-bold">
                {{ translate('Secure Login') }}
            </button>
        </form>
    </div>
</div>

<script src="{{ asset('public/assets/admin/js/bootstrap.bundle.min.js') }}"></cript>
<script src="{{ asset('public/assets/admin/js/toastr.js') }}"></script>
{!! Toastr::message() !!}

</body>
</html>
