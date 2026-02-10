<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Attendance;
use App\Model\Branch;
use App\Model\DeliveryMan;
use App\Model\Admin;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function __construct(
        private Attendance $attendance,
        private Branch $branch,
        private DeliveryMan $deliveryMan,
        private Admin $admin
    ) {}

    /**
     * Display attendance list with filters
     */
    public function index(Request $request): View|Factory|Application
    {
        $search = $request->get('search');
        $branchId = $request->get('branch_id');
        $userType = $request->get('user_type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = $this->attendance->with(['branch']);

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('branch', function ($branchQuery) use ($search) {
                    $branchQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($userType) {
            $query->where('user_type', $userType);
        }

        if ($dateFrom) {
            $query->whereDate('check_in', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('check_in', '<=', $dateTo);
        }

        $attendances = $query->latest('check_in')->paginate(Helpers::getPagination());
        $branches = $this->branch->all();

        return view('admin-views.attendance.index', compact(
            'attendances',
            'branches',
            'search',
            'branchId',
            'userType',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Show check-in/check-out form
     */
    public function checkInOut(): View|Factory|Application
    {
        $branches = $this->branch->all();
        $deliveryMen = $this->deliveryMan->where('application_status', 'approved')->get();
        $employees = $this->admin->whereNotIn('id', [1])->get();

        return view('admin-views.attendance.check-in-out', compact('branches', 'deliveryMen', 'employees'));
    }

    /**
     * Process check-in
     */
    public function checkIn(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required',
            'user_type' => 'required|in:delivery_man,admin',
            'branch_id' => 'required|exists:branches,id',
        ], [
            'user_id.required' => translate('Employee is required'),
            'user_type.required' => translate('User type is required'),
            'branch_id.required' => translate('Branch is required'),
        ]);

        // Check if already checked in today
        $existingAttendance = $this->attendance
            ->where('user_id', $request->user_id)
            ->where('user_type', $request->user_type)
            ->whereDate('check_in', Carbon::today())
            ->whereNull('check_out')
            ->first();

        if ($existingAttendance) {
            Toastr::warning(translate('Employee already checked in today'));
            return back();
        }

        $attendance = new Attendance();
        $attendance->user_id = $request->user_id;
        $attendance->user_type = $request->user_type;
        $attendance->branch_id = $request->branch_id;
        $attendance->check_in = now();
        $attendance->notes = $request->notes;
        $attendance->save();

        Toastr::success(translate('Checked in successfully'));
        return back();
    }

    /**
     * Process check-out
     */
    public function checkOut(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required',
            'user_type' => 'required|in:delivery_man,admin',
        ], [
            'user_id.required' => translate('Employee is required'),
            'user_type.required' => translate('User type is required'),
        ]);

        $attendance = $this->attendance
            ->where('user_id', $request->user_id)
            ->where('user_type', $request->user_type)
            ->whereDate('check_in', Carbon::today())
            ->whereNull('check_out')
            ->first();

        if (!$attendance) {
            Toastr::error(translate('No active check-in found for today'));
            return back();
        }

        $checkOut = now();
        $totalMinutes = $checkOut->diffInMinutes($attendance->check_in);

        $attendance->check_out = $checkOut;
        $attendance->total_hours = $totalMinutes;
        if ($request->notes) {
            $attendance->notes = $attendance->notes . ' | ' . $request->notes;
        }
        $attendance->save();

        Toastr::success(translate('Checked out successfully'));
        return back();
    }

    /**
     * Export attendance to Excel
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $branchId = $request->get('branch_id');
        $userType = $request->get('user_type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = $this->attendance->with(['branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($userType) {
            $query->where('user_type', $userType);
        }

        if ($dateFrom) {
            $query->whereDate('check_in', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('check_in', '<=', $dateTo);
        }

        $attendances = $query->latest('check_in')->get();

        $data = [];
        foreach ($attendances as $key => $attendance) {
            $userName = $this->getUserName($attendance->user_id, $attendance->user_type);
            
            $data[] = [
                'SL' => $key + 1,
                'Employee' => $userName,
                'Type' => ucfirst(str_replace('_', ' ', $attendance->user_type)),
                'Branch' => $attendance->branch?->name ?? 'N/A',
                'Check In' => $attendance->check_in ? $attendance->check_in->format('Y-m-d H:i:s') : 'N/A',
                'Check Out' => $attendance->check_out ? $attendance->check_out->format('Y-m-d H:i:s') : 'N/A',
                'Total Hours' => $attendance->total_hours_formatted,
                'Notes' => $attendance->notes ?? '',
            ];
        }

        return (new FastExcel($data))->download('attendance_' . time() . '.xlsx');
    }

    /**
     * Export attendance to PDF
     */
    public function exportPdf(Request $request)
    {
        $branchId = $request->get('branch_id');
        $userType = $request->get('user_type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = $this->attendance->with(['branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($userType) {
            $query->where('user_type', $userType);
        }

        if ($dateFrom) {
            $query->whereDate('check_in', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('check_in', '<=', $dateTo);
        }

        $attendances = $query->latest('check_in')->get();

        $data = [
            'attendances' => $attendances,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];

        $pdf = Pdf::loadView('admin-views.attendance.pdf', $data);
        return $pdf->download('attendance_' . time() . '.pdf');
    }

    /**
     * Get user name based on user type
     */
    private function getUserName($userId, $userType): string
    {
        if ($userType === 'delivery_man') {
            $user = $this->deliveryMan->find($userId);
            return $user ? ($user->f_name . ' ' . $user->l_name) : 'N/A';
        } else {
            $user = $this->admin->find($userId);
            return $user ? ($user->f_name . ' ' . $user->l_name) : 'N/A';
        }
    }

    /**
     * Get monthly report
     */
    public function monthlyReport(Request $request): View|Factory|Application
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $branchId = $request->get('branch_id');
        $userType = $request->get('user_type');

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();

        $query = $this->attendance
            ->whereBetween('check_in', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($userType) {
            $query->where('user_type', $userType);
        }

        $attendances = $query->get();

        // Group by user
        $summary = $attendances->groupBy(function ($item) {
            return $item->user_type . '_' . $item->user_id;
        })->map(function ($items) {
            $totalMinutes = $items->sum('total_hours');
            $totalDays = $items->count();
            
            return [
                'user_id' => $items->first()->user_id,
                'user_type' => $items->first()->user_type,
                'total_minutes' => $totalMinutes,
                'total_days' => $totalDays,
                'total_hours_formatted' => sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60),
            ];
        });

        $branches = $this->branch->all();

        return view('admin-views.attendance.monthly-report', compact(
            'summary',
            'month',
            'branchId',
            'userType',
            'branches'
        ));
    }
}
