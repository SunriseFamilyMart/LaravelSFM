@extends('layouts.admin.app')

@section('title', translate('Attendance Management'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <i class="tio-time"></i>
            </span>
            <span>
                {{translate('Attendance Records')}}<span class="badge badge-soft-primary ml-2">{{$attendances->total()}}</span>
            </span>
        </h1>
    </div>

    <div class="card">
        <div class="card-header border-0">
            <div class="card--header justify-content-between">
                <h5 class="card-title">{{translate('Filter Attendance')}}</h5>
                <div class="d-flex gap-2">
                    <a href="{{route('admin.attendance.check-in-out')}}" class="btn btn--primary">
                        <i class="tio-time"></i>
                        <span class="text">{{translate('Check In/Out')}}</span>
                    </a>
                    <a href="{{route('admin.attendance.monthly-report')}}" class="btn btn-outline-primary">
                        <i class="tio-chart-bar-1"></i>
                        <span class="text">{{translate('Monthly Report')}}</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="{{url()->current()}}" method="GET">
                <div class="row g-3">
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
                    <div class="col-sm-6 col-md-2">
                        <label class="input-label">{{translate('Date From')}}</label>
                        <input type="date" name="date_from" class="form-control" value="{{$dateFrom}}">
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <label class="input-label">{{translate('Date To')}}</label>
                        <input type="date" name="date_to" class="form-control" value="{{$dateTo}}">
                    </div>
                    <div class="col-sm-12 col-md-2">
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
                <h5 class="card-title">{{translate('Attendance List')}}</h5>
                <div class="hs-unfold">
                    <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle btn export-btn btn-outline-primary-2 btn--primary font--sm" href="javascript:;"
                       data-hs-unfold-options='{
                            "target": "#usersExportDropdown",
                            "type": "css-animation"
                            }'>
                        <i class="tio-download-to mr-1"></i> {{translate('export')}}
                    </a>

                    <div id="usersExportDropdown" class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                        <span class="dropdown-header">{{translate('download')}} {{translate('options')}}</span>
                        <a class="dropdown-item" href="{{route('admin.attendance.export-excel', request()->query())}}">
                            <img class="avatar avatar-xss avatar-4by3 mr-2" src="{{asset('public/assets/admin')}}/svg/components/excel.svg" alt="{{ translate('excel') }}">
                            {{translate('excel')}}
                        </a>
                        <a class="dropdown-item" href="{{route('admin.attendance.export-pdf', request()->query())}}">
                            <i class="tio-download-to mr-2"></i>
                            {{translate('PDF')}}
                        </a>
                    </div>
                </div>
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
                        <th>{{translate('Branch')}}</th>
                        <th>{{translate('Check In')}}</th>
                        <th>{{translate('Check Out')}}</th>
                        <th>{{translate('Total Hours')}}</th>
                        <th>{{translate('Notes')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($attendances as $k => $attendance)
                        <tr>
                            <td>{{$attendances->firstItem()+$k}}</td>
                            <td>
                                @php
                                    $userName = 'N/A';
                                    if($attendance->user_type === 'delivery_man') {
                                        $user = \App\Model\DeliveryMan::find($attendance->user_id);
                                        $userName = $user ? ($user->f_name . ' ' . $user->l_name) : 'N/A';
                                    } else {
                                        $user = \App\Model\Admin::find($attendance->user_id);
                                        $userName = $user ? ($user->f_name . ' ' . $user->l_name) : 'N/A';
                                    }
                                @endphp
                                {{$userName}}
                            </td>
                            <td>
                                <span class="badge badge-soft-{{$attendance->user_type === 'admin' ? 'info' : 'success'}}">
                                    {{ucfirst(str_replace('_', ' ', $attendance->user_type))}}
                                </span>
                            </td>
                            <td>{{$attendance->branch?->name ?? 'N/A'}}</td>
                            <td>
                                <div>{{$attendance->check_in ? $attendance->check_in->format('Y-m-d') : 'N/A'}}</div>
                                <div class="text-muted">{{$attendance->check_in ? $attendance->check_in->format('H:i:s') : ''}}</div>
                            </td>
                            <td>
                                @if($attendance->check_out)
                                    <div>{{$attendance->check_out->format('Y-m-d')}}</div>
                                    <div class="text-muted">{{$attendance->check_out->format('H:i:s')}}</div>
                                @else
                                    <span class="badge badge-soft-warning">{{translate('Active')}}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-soft-primary">{{$attendance->total_hours_formatted}}</span>
                            </td>
                            <td>{{Str::limit($attendance->notes ?? '', 50)}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{$attendances->links()}}
            </div>
            @if(count($attendances)==0)
                <div class="text-center p-4">
                    <img class="w-120px mb-3" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{ translate('image') }}">
                    <p class="mb-0">{{ translate('No_data_to_show')}}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
