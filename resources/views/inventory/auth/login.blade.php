<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Inventory Login') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: #c2c6cb;
        }

        .login-card {
            border-radius: 12px;
            overflow: hidden;
        }

        .login-card .card-body {
            padding: 3rem 2.5rem;
        }

        .login-card h2 {
            font-size: 1.8rem;
        }

        .login-card .small-link a {
            text-decoration: none;
        }

        .input-group-text {
            cursor: pointer;
        }

        @media (max-width: 991px) {
            .login-card img {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">

            <!-- Login Form Column -->
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-sm login-card">
                    <div class="card-body">
                        <h2 class="mb-4 fw-bold text-center">{{ translate('Inventory Login') }}</h2>
                        @if (session('error'))
                            <div class="alert alert-warning">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('inventory.auth.login.submit') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">{{ translate('Email') }}</label>
                                <input type="email" name="email" id="email" class="form-control"
                                    placeholder="{{ translate('Enter your email') }}" required autofocus
                                    value="{{ old('email') }}">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">{{ translate('Password') }}</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control"
                                        placeholder="{{ translate('Enter your password') }}" required>
                                    <span class="input-group-text" id="togglePassword">
                                        <i class="fa-solid fa-eye"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="remember" id="remember">
                                <label class="form-check-label" for="remember">{{ translate('Remember Me') }}</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">{{ translate('Login') }}</button>
                        </form>

                        <p class="mt-4 text-center small small-link">
                            {{ translate('Want to login your Admin?') }}<br>
                            <a href="{{ route('admin.auth.login') }}">{{ translate('Admin Login') }}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Show/Hide Password Script -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // Toggle Font Awesome icon
            this.innerHTML = type === 'password' ?
                '<i class="fa-solid fa-eye"></i>' :
                '<i class="fa-solid fa-eye-slash"></i>';
        });
    </script>

</body>

</html>
