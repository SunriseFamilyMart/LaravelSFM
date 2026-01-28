<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Sales Manager' }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
            font-family: "Inter", sans-serif;
        }

        /* Sidebar */
        .sidebar {
            width: 245px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(180deg, #5b6cff, #3b49d7);
            color: #fff;
            padding-top: 15px;
            box-shadow: 3px 0px 15px rgba(0, 0, 0, 0.1);
            transition: 0.3s ease;
            z-index: 3000;
            /* FIXED → Sidebar above overlay */
        }

        .sidebar h4 {
            font-size: 20px;
            margin-bottom: 25px;
        }

        .sidebar .nav-link {
            color: #dcdffe;
            padding: 12px 20px;
            font-size: 15px;
            border-radius: 8px;
            margin: 4px 12px;
            transition: 0.25s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-weight: 500;
        }

        /* Close button for mobile */
        .close-sidebar-btn {
            display: none;
        }

        @media (max-width: 768px) {
            .close-sidebar-btn {
                display: block;
                margin-left: 15px;
                margin-bottom: 10px;
            }

            .sidebar {
                left: -245px;
            }

            .sidebar.open {
                left: 0;
            }

            .main-content {
                margin-left: 0 !important;
            }
        }

        /* Overlay */
        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            z-index: 2000;
            /* FIXED → Overlay below sidebar */
        }

        .overlay.show {
            display: block;
        }

        /* Main content */
        .main-content {
            margin-left: 245px;
            padding: 20px;
            transition: 0.3s ease;
        }

        /* Top Navbar */
        .main-navbar {
            background: #fff;
            border-bottom: 1px solid #e1e1e1;
            padding: 12px 25px;
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>

</head>

<body>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">

        <!-- Mobile Close Button -->
        <button class="btn btn-light btn-sm close-sidebar-btn d-md-none" id="closeSidebar">
            <i class="bi bi-x-lg"></i>
        </button>

        <h4 class="text-center">Sales Manager</h4>

        <nav class="nav flex-column">
            <a class="nav-link {{ Request::is('sales/dashboard') ? 'active' : '' }}"
                href="{{ route('sales.dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>

            <a class="nav-link {{ Request::is('sales/report') ? 'active' : '' }}" href="{{ route('sales.report') }}">
                <i class="bi bi-clipboard-data me-2"></i> Reports
            </a>

            <a class="nav-link {{ Request::is('sales/team*') ? 'active' : '' }}" href="{{ route('sales.team.index') }}">
                <i class="bi bi-people me-2"></i> Team Members
            </a>

            <a class="nav-link {{ Request::is('sales/performance') ? 'active' : '' }}"
                href="{{ route('sales.performance') }}">
                <i class="bi bi-graph-up me-2"></i> Performance
            </a>

        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Top Navbar -->
        <div class="main-navbar">

            <!-- Toggle button for mobile -->
            <button class="btn btn-primary d-md-none" id="toggleSidebar">
                <i class="bi bi-list"></i>
            </button>

            <div class="page-title">{{ $title ?? '' }}</div>

            <div>
                <span class="user-info me-3">
                    Welcome, <strong>{{ session('sales_user_name') }}</strong>
                </span>

                <a href="{{ route('sales.logout') }}" class="btn btn-sm btn-danger logout-btn">
                    Logout
                </a>
            </div>
        </div>

        <!-- Page Content -->
        <div class="mt-4">
            @yield('content')
        </div>
    </div>

    <!-- Sidebar Script -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        const overlay = document.getElementById('overlay');
        const closeBtn = document.getElementById('closeSidebar');

        // Open sidebar
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.add('open');
            overlay.classList.add('show');
        });

        // Close with X button
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });

        // Close when clicking overlay
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });

        // Close when clicking a menu link on mobile
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('show');
                }
            });
        });
    </script>

</body>

</html>
