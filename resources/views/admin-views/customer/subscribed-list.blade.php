@extends('layouts.admin.app')

@section('title', translate('Subscribed List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/employee.png')}}" class="w--20" alt="{{ translate('employee') }}">
                </span>
                <span>
                    {{translate('Subscribed Customers')}} <span class="badge badge-soft-primary ml-2 badge-pill">{{ $newsletters->total() }}</span>
                </span>
            </h1>
        </div>

        <div class="card">
            <div class="card-header">

                <div class="card--header">
                    <form action="{{url()->current()}}" method="GET">
                        <div class="input-group">
                            <input id="datatableSearch_" type="search" name="search"
                                   class="form-control"
                                   placeholder="{{translate('Ex : Search Emails Address')}}" aria-label="Search"
                                   value="{{$search}}" required autocomplete="off">
                            <div class="input-group-append">
                                <button type="submit" class="input-group-text">
                                    {{ translate('search') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="hs-unfold ml-auto">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-outline-primary-2 dropdown-toggle min-height-40" href="javascript:;"
                           data-hs-unfold-options='{
                            "target": "#usersExportDropdown",
                            "type": "css-animation"
                            }'>
                            <i class="tio-download-to mr-1"></i> {{ translate('export') }}
                        </a>

                        <div id="usersExportDropdown"
                             class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                            <span class="dropdown-header">{{ translate('download') }}
                                {{ translate('options') }}</span>
                            <a id="export-excel" class="dropdown-item" href="{{route('admin.customer.subscribed_emails_export', ['search'=>$search])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                     alt="{{ translate('excel') }}">
                                {{ translate('excel') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive datatable-custom">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                    <thead class="thead-light">
                    <tr>
                        <th>{{translate('#')}}</th>
                        <th>{{translate('email')}}</th>
                        <th>{{translate('subscribed_at')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($newsletters as $key=>$newsletter)
                        <tr >
                            <td>
                                {{$newsletters->firstitem()+$key}}
                            </td>
                            <td>
                                <a href="mailto:{{$newsletter['email']}}?subject={{translate('Mail from '). Helpers::get_business_settings('restaurant_name')}}">{{$newsletter['email']}}</a>
                            </td>
                            <td>{{date('d M Y h:m A '.config('timeformat'), strtotime($newsletter->created_at))}}</td>
                        </tr>

                    @endforeach

                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <div class="row">
                    <div class="col-12">
                        {!! $newsletters->links() !!}
                    </div>
                </div>
            </div>
            @if(count($newsletters) == 0)
                <div class="text-center p-4">
                    <img class="w-120px mb-3" src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="{{ translate('image') }}">
                    <p class="mb-0">{{translate('No_data_to_show')}}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
