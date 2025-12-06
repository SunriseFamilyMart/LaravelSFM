@extends('sales.layout')

@section('title', 'Team Members')

@section('content')

    <h4 class="fw-bold mb-3">All Sales People</h4>

    {{-- ✅ Search + Reset --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">

                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" value="{{ request()->search }}"
                        placeholder="Search by name, email or phone">
                </div>

                <div class="col-md-3">
                    <button class="btn btn-primary w-100">Search</button>
                </div>

                <div class="col-md-3">
                    <a href="{{ route('sales.team.index') }}" class="btn btn-secondary w-100">
                        Reset
                    </a>
                </div>

            </form>
        </div>
    </div>

    <div class="row">

        @foreach ($teamSummary as $item)
            @php $p = $item['person']; @endphp

            <div class="col-md-4 mb-4">
                <div class="team-card p-3 rounded-4 shadow-sm">

                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ asset('storage/' . ($p->person_photo ?? 'default.jpg')) }}"
                            class="rounded-circle me-3 shadow" width="70" height="70">

                        <div>
                            <h5 class="fw-bold mb-1">{{ $p->name }}</h5>
                            <p class="text-muted small mb-0">{{ $p->email }}</p>
                            <p class="text-muted small">{{ $p->phone_number }}</p>
                        </div>
                    </div>

                    <div class="stats">
                        <div class="stat-box gradient1">
                            <strong>{{ $item['store_count'] }}</strong>
                            <span>Stores</span>
                        </div>
                        <div class="stat-box gradient2">
                            <strong>{{ $item['order_count'] }}</strong>
                            <span>Orders</span>
                        </div>
                        <div class="stat-box gradient3">
                            <strong>₹{{ number_format($item['total_amount'], 2) }}</strong>
                            <span>Sales</span>
                        </div>
                        <div class="stat-box gradient4">
                            <strong>{{ $item['visit_count'] }}</strong>
                            <span>Visits</span>
                        </div>
                    </div>

                </div>
            </div>
        @endforeach

    </div>

    {{-- ✅ Pagination --}}
    <div class="mt-3">
        {{ $teamSummary->links('pagination::bootstrap-5') }}
    </div>

@endsection


<style>
    .team-card {
        background: #fff;
        transition: .3s;
        cursor: pointer
    }

    .team-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, .12)
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-top: 20px
    }

    .stat-box {
        padding: 12px;
        border-radius: 12px;
        color: #fff;
        text-align: center;
        font-size: 14px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, .1);
        transition: .3s
    }

    .stat-box strong {
        display: block;
        font-size: 18px;
        font-weight: 700
    }

    .stat-box:hover {
        opacity: .9
    }

    .gradient1 {
        background: linear-gradient(135deg, #5b6cff, #20c997)
    }

    .gradient2 {
        background: linear-gradient(135deg, #ff7f50, #ff416c)
    }

    .gradient3 {
        background: linear-gradient(135deg, #6a11cb, #2575fc)
    }

    .gradient4 {
        background: linear-gradient(135deg, #ff9a9e, #fad0c4)
    }
</style>
