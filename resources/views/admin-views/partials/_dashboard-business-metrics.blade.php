<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-primary">
        <h6 class="subtitle text-white">{{translate('total_sales')}}</h6>
        <h2 class="title text-white">
            {{ Helpers::set_symbol(number_format($metrics['total_sales'], 2)) }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/sales.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-success">
        <h6 class="subtitle text-white">{{translate('total_purchases')}}</h6>
        <h2 class="title text-white">
            {{ Helpers::set_symbol(number_format($metrics['total_purchases'], 2)) }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/purchases.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-info">
        <h6 class="subtitle text-white">{{translate('total_orders')}}</h6>
        <h2 class="title text-white">
            {{ $metrics['total_orders'] }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/orders.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-warning">
        <h6 class="subtitle text-white">{{translate('delivered_orders')}}</h6>
        <h2 class="title text-white">
            {{ $metrics['delivered_orders'] }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/delivered.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-danger">
        <h6 class="subtitle text-white">{{translate('profit')}}</h6>
        <h2 class="title text-white">
            {{ Helpers::set_symbol(number_format($metrics['profit'], 2)) }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/profit.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-dark">
        <h6 class="subtitle text-white">{{translate('margin')}}</h6>
        <h2 class="title text-white">
            {{ number_format($metrics['margin'], 2) }}%
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/margin.png')}}" alt="" class="dashboard-icon">
    </div>
</div>
