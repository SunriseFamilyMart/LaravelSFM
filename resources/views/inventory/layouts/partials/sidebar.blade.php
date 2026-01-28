<div class="sidebar-header text-uppercase fw-bold text-white px-3 py-3 border-bottom">
    <i class="fa-solid fa-boxes-stacked me-2"></i> {{ translate('Inventory') }}
</div>

<ul class="nav flex-column mt-3">
    {{-- Dashboard --}}
    <li class="nav-item mb-1">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('inventory.dashboard') ? 'active bg-light text-primary fw-semibold' : 'text-white' }}"
            href="{{ route('inventory.dashboard') }}">
            <i class="fa-solid fa-house text-primary"></i>
            <span>{{ translate('Dashboard') }}</span>
        </a>
    </li>

    <li class="nav-item mb-1">
        <a class="nav-link d-flex align-items-center gap-2  {{ request()->routeIs('inventory.suppliers.*') ? 'active bg-light text-primary fw-semibold' : 'text-white' }}"
            href="{{ route('inventory.suppliers.index') }}">
            <i class="fa-solid fa-receipt text-success"></i>
            <span>{{ translate('Suppliers') }}</span>
        </a>
    </li>

    {{-- Purchases --}}
    <li class="nav-item mb-1">
        <a class="nav-link d-flex align-items-center gap-2  {{ request()->routeIs('inventory.purchases.*') ? 'active bg-light text-primary fw-semibold' : 'text-white' }}"
            href="{{ route('inventory.purchases.index') }}">
            <i class="fa-solid fa-receipt text-success"></i>
            <span>{{ translate('Purchases') }}</span>
        </a>
    </li>

    {{-- Category Sales Report --}}
    <li class="nav-item mb-1">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('inventory.reports.category_sales') ? 'active bg-light text-primary fw-semibold' : 'text-white' }}"
            href="{{ route('inventory.reports.category_sales') }}">
            <i class="fa-solid fa-chart-pie text-info"></i>
            <span>{{ translate('Category Sales Report') }}</span>
        </a>
    </li>

    <li class="nav-item mb-1">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('inventory.stock.report') ? 'active bg-light text-primary fw-semibold' : 'text-white' }}"
            href="{{ route('inventory.stock.report') }}">
            <i class="fa-solid fa-chart-pie text-info"></i>
            <span>{{ translate('Stock Report') }}</span>
        </a>
    </li>
    {{-- Products --}}
    <li class="nav-item mb-1">
        <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('inventory.products.index') ? 'active bg-light text-primary fw-semibold' : 'text-white' }}"
            href="{{ route('inventory.products.index') }}">
            <i class="fa-solid fa-chart-pie text-info"></i>
            <span>{{ translate('Product List') }}</span>
        </a>
    </li>
</ul>
