<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-primary">
        <h6 class="subtitle text-white">{{translate('total_sales')}}</h6>
        <h2 class="title text-white">
            {{ Helpers::set_symbol($metrics['total_sales']) }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/1.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-success">
        <h6 class="subtitle text-white">{{translate('total_purchases')}}</h6>
        <h2 class="title text-white">
            {{ Helpers::set_symbol($metrics['total_purchases']) }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/2.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-info">
        <h6 class="subtitle text-white">{{translate('total_orders')}}</h6>
        <h2 class="title text-white">
            {{ $metrics['total_orders'] }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/3.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-warning">
        <h6 class="subtitle text-white">{{translate('delivered_orders')}}</h6>
        <h2 class="title text-white">
            {{ $metrics['delivered_orders'] }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/4.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-danger">
        <h6 class="subtitle text-white">{{translate('profit')}}</h6>
        <h2 class="title text-white">
            {{ Helpers::set_symbol($metrics['profit']) }}
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/confirmed.png')}}" alt="" class="dashboard-icon">
    </div>
</div>

<div class="col-sm-6 col-lg-4">
    <div class="dashboard--card h-100 bg-gradient-dark">
        <h6 class="subtitle text-white">{{translate('margin')}}</h6>
        <h2 class="title text-white">
            {{ number_format($metrics['margin'], 2) }}%
        </h2>
        <img src="{{asset('/public/assets/admin/img/dashboard/pending.png')}}" alt="" class="dashboard-icon">
    </div>
</div>
