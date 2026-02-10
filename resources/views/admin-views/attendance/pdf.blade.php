<!DOCTYPE html>
<html>
<head>
    <title>{{translate('Attendance Report')}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .info {
            text-align: center;
            margin-bottom: 20px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            background-color: #e3f2fd;
        }
    </style>
</head>
<body>
    <h1>{{translate('Attendance Report')}}</h1>
    <div class="info">
        @if($dateFrom && $dateTo)
            {{translate('Period')}}: {{$dateFrom}} {{translate('to')}} {{$dateTo}}
        @endif
        <br>
        {{translate('Generated on')}}: {{\Carbon\Carbon::now()->format('Y-m-d H:i:s')}}
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">{{translate('SL')}}</th>
                <th>{{translate('Employee')}}</th>
                <th>{{translate('Type')}}</th>
                <th>{{translate('Branch')}}</th>
                <th>{{translate('Check In')}}</th>
                <th>{{translate('Check Out')}}</th>
                <th>{{translate('Total Hours')}}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $k => $attendance)
                <tr>
                    <td class="text-center">{{$k + 1}}</td>
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
                    <td>{{ucfirst(str_replace('_', ' ', $attendance->user_type))}}</td>
                    <td>{{$attendance->branch?->name ?? 'N/A'}}</td>
                    <td>{{$attendance->check_in ? $attendance->check_in->format('Y-m-d H:i:s') : 'N/A'}}</td>
                    <td>{{$attendance->check_out ? $attendance->check_out->format('Y-m-d H:i:s') : 'Active'}}</td>
                    <td>{{$attendance->total_hours_formatted}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($attendances) == 0)
        <p style="text-align: center; margin-top: 20px;">{{translate('No data to show')}}</p>
    @endif
</body>
</html>
