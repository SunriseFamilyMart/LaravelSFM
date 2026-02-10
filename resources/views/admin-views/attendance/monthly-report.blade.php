@extends('layouts.admin.app')

@section('title', translate('Monthly Attendance Report'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <i class="tio-chart-bar-1"></i>
            </span>
            <span>
                {{translate('Monthly Attendance Report')}}
            </span>
        </h1>
    </div>

    <div class="card">
        <div class="card-header border-0">
            <div class="card--header">
                <h5 class="card-title">{{translate('Filter Report')}}</h5>
            </div>
        </div>
        <div class="card-body">
            <form action="{{url()->current()}}" method="GET">
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <label class="input-label">{{translate('Month')}}</label>
                        <input type="month" name="month" class="form-control" value="{{$month}}" required>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="input-label">{{translate('Branch')}}</label>
                        <select name="branch_id" class="form-control">
                            <option value="">{{translate('All Branches')}}</option>
                            @foreach($branches as $branch)
                                <option value="{{$branch->id}}" {{$branchId == $branch->id ? 'selected' : ''}}>
                                    {{$branch->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="input-label">{{translate('User Type')}}</label>
                        <select name="user_type" class="form-control">
                            <option value="">{{translate('All Types')}}</option>
                            <option value="admin" {{$userType == 'admin' ? 'selected' : ''}}>{{translate('Employee')}}</option>
                            <option value="delivery_man" {{$userType == 'delivery_man' ? 'selected' : ''}}>{{translate('Delivery Man')}}</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="input-label d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn--primary btn-block">
                            <i class="tio-filter-list"></i> {{translate('Filter')}}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header border-0">
            <div class="card--header">
                <h5 class="card-title">{{translate('Summary Report')}}</h5>
            </div>
        </div>
        <div class="card-body px-0 pt-0">
            <div class="table-responsive">
                <table class="table table-borderless table-hover table-align-middle m-0 text-14px">
                    <thead class="thead-light">
                    <tr>
                        <th>{{translate('SL')}}</th>
                        <th>{{translate('Employee')}}</th>
                        <th>{{translate('Type')}}</th>
                        <th>{{translate('Total Days')}}</th>
                        <th>{{translate('Total Hours')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php $sl = 1; @endphp
                    @foreach($summary as $item)
                        <tr>
                            <td>{{$sl++}}</td>
                            <td>
                                @php
                                    $userName = 'N/A';
                                    if($item['user_type'] === 'delivery_man') {
                                        $user = \App\Model\DeliveryMan::find($item['user_id']);
                                        $userName = $user ? ($user->f_name . ' ' . $user->l_name) : 'N/A';
                                    } else {
                                        $user = \App\Model\Admin::find($item['user_id']);
                                        $userName = $user ? ($user->f_name . ' ' . $user->l_name) : 'N/A';
                                    }
                                @endphp
                                {{$userName}}
                            </td>
                            <td>
                                <span class="badge badge-soft-{{$item['user_type'] === 'admin' ? 'info' : 'success'}}">
                                    {{ucfirst(str_replace('_', ' ', $item['user_type']))}}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-soft-primary">{{$item['total_days']}}</span>
                            </td>
                            <td>
                                <span class="badge badge-soft-success">{{$item['total_hours_formatted']}}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($summary)==0)
                <div class="text-center p-4">
                    <img class="w-120px mb-3" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{ translate('image') }}">
                    <p class="mb-0">{{ translate('No_data_to_show')}}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
