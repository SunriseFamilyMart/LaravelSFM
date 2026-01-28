<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Inventory Dashboard') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar {
            min-width: 220px;
            max-width: 220px;
            background-color: #0d6efd;
            color: white;
            min-height: 100vh;
            transition: transform 0.3s ease;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: #0b5ed7;
            color: white;
        }

        .content {
            flex: 1;
            padding: 20px;
            background-color: #f8f9fa;
            transition: margin-left 0.3s ease;
        }

        .sidebar .nav-link.active {
            background-color: #0b5ed7;
        }

        .sidebar-header {
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar-custom {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Mobile Styles */
        @media (max-width: 991px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                z-index: 1050;
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
            }

            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: none;
                z-index: 1040;
            }

            .overlay.show {
                display: block;
            }

            .sidebar-toggle-btn {
                display: inline-block;
            }
        }

        /* Desktop Styles */
        @media (min-width: 992px) {
            .sidebar-toggle-btn {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            @include('inventory.layouts.partials.sidebar')
        </div>

        <!-- Overlay for mobile -->
        <div class="overlay" id="overlay"></div>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Header -->
            <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
                <div class="container-fluid">
                    <button class="btn btn-primary sidebar-toggle-btn" id="sidebarToggle">
                        &#9776; <!-- Hamburger icon -->
                    </button>
                    <span class="ms-2">{{ translate('Inventory Dashboard') }}</span>
                    @include('inventory.layouts.partials.header')
                </div>
            </nav>

            <!-- Content Area -->
            <div class="content">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Toggle Script -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    </script>
</body>

</html>
