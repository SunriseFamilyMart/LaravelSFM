@extends('layouts.admin.app')

@section('title', translate('Check In / Check Out'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <i class="tio-time"></i>
            </span>
            <span>
                {{translate('Employee Check In / Check Out')}}
            </span>
        </h1>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-checkmark-circle"></i> {{translate('Check In')}}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{route('admin.attendance.check-in')}}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">{{translate('User Type')}} <span class="text-danger">*</span></label>
                            <select name="user_type" id="check_in_user_type" class="form-control" required>
                                <option value="">{{translate('Select Type')}}</option>
                                <option value="admin">{{translate('Employee')}}</option>
                                <option value="delivery_man">{{translate('Delivery Man')}}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{translate('Employee')}} <span class="text-danger">*</span></label>
                            <select name="user_id" id="check_in_user_id" class="form-control" required>
                                <option value="">{{translate('Select Employee')}}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{translate('Branch')}} <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">{{translate('Select Branch')}}</option>
                                @foreach($branches as $branch)
                                    <option value="{{$branch->id}}">{{$branch->name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{translate('Notes')}} <span class="text-muted">({{translate('Optional')}})</span></label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="{{translate('Enter any notes')}}"></textarea>
                        </div>

                        <button type="submit" class="btn btn--primary btn-block">
                            <i class="tio-checkmark-circle"></i> {{translate('Check In')}}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="tio-clear-circle"></i> {{translate('Check Out')}}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{route('admin.attendance.check-out')}}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="input-label">{{translate('User Type')}} <span class="text-danger">*</span></label>
                            <select name="user_type" id="check_out_user_type" class="form-control" required>
                                <option value="">{{translate('Select Type')}}</option>
                                <option value="admin">{{translate('Employee')}}</option>
                                <option value="delivery_man">{{translate('Delivery Man')}}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{translate('Employee')}} <span class="text-danger">*</span></label>
                            <select name="user_id" id="check_out_user_id" class="form-control" required>
                                <option value="">{{translate('Select Employee')}}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{translate('Notes')}} <span class="text-muted">({{translate('Optional')}})</span></label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="{{translate('Enter any notes')}}"></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="tio-clear-circle"></i> {{translate('Check Out')}}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script_2')
<script>
    var employees = @json($employees);
    var deliveryMen = @json($deliveryMen);

    // Check In user type change
    $('#check_in_user_type').on('change', function() {
        var userType = $(this).val();
        var $select = $('#check_in_user_id');
        $select.empty().append('<option value="">{{translate("Select Employee")}}</option>');

        if (userType === 'admin') {
            employees.forEach(function(emp) {
                $select.append('<option value="' + emp.id + '">' + emp.f_name + ' ' + emp.l_name + '</option>');
            });
        } else if (userType === 'delivery_man') {
            deliveryMen.forEach(function(dm) {
                $select.append('<option value="' + dm.id + '">' + dm.f_name + ' ' + dm.l_name + '</option>');
            });
        }
    });

    // Check Out user type change
    $('#check_out_user_type').on('change', function() {
        var userType = $(this).val();
        var $select = $('#check_out_user_id');
        $select.empty().append('<option value="">{{translate("Select Employee")}}</option>');

        if (userType === 'admin') {
            employees.forEach(function(emp) {
                $select.append('<option value="' + emp.id + '">' + emp.f_name + ' ' + emp.l_name + '</option>');
            });
        } else if (userType === 'delivery_man') {
            deliveryMen.forEach(function(dm) {
                $select.append('<option value="' + dm.id + '">' + dm.f_name + ' ' + dm.l_name + '</option>');
            });
        }
    });
</script>
@endpush
@endsection
