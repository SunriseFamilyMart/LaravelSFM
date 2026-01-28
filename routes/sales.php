<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sales\Auth\SalesAuthController;
use App\Http\Controllers\Sales\TeamController;
use App\Models\Order;
use App\Models\Store;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

Route::prefix('sales')->name('sales.')->group(function () {

    // Login page
    Route::get('/', function () {
        return view('sales.login');
    })->name('login');

    Route::post('/login', [SalesAuthController::class, 'login'])->name('login.post');

    // Protected routes
    Route::middleware('sales.auth')->group(function () {

        Route::get('/dashboard', function () {
            return view('sales.dashboard');
        })->name('dashboard');

        // Logout
        Route::get('/logout', function () {
            session()->forget([
                'sales_logged_in',
                'sales_user_id',
                'sales_user_role',
                'sales_user_name',
            ]);
            return redirect()->route('sales.login')->with('success', 'Logged out successfully');
        })->name('logout');

        // Sales report page (Blade view)
        Route::get('/report', function () {
            return view('sales.report');
        })->name('report');

        // Excel download route (must be separate to work properly)
        Route::get('/report/download', function () {

            $salesPersonId = session('sales_user_id');
            $stores = Store::where('sales_person_id', $salesPersonId)->get();
            $storeIds = $stores->pluck('id');

            $search = request()->search ?? '';
            $filter = request()->filter ?? '';

            $ordersQuery = Order::whereIn('store_id', $storeIds)->orderBy('created_at', 'DESC');

            if ($search != '') {
                $ordersQuery->where(function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%$search%")
                        ->orWhere('order_amount', 'LIKE', "%$search%");
                });
            }

            if ($filter == 'today') {
                $ordersQuery->whereDate('created_at', today());
            } elseif ($filter == 'week') {
                $ordersQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($filter == 'month') {
                $ordersQuery->whereMonth('created_at', now()->month);
            }

            $allOrders = $ordersQuery->get();

            return Excel::download(new class ($allOrders, $stores) implements FromCollection, WithHeadings {
                protected $orders;
                protected $stores;
                public function __construct($orders, $stores)
                {
                    $this->orders = $orders;
                    $this->stores = $stores;
                }
                public function collection()
                {
                    return $this->orders->map(function ($order) {
                        $storeName = $this->stores->where('id', $order->store_id)->first()->store_name ?? 'Unknown Store';
                        return [
                            'Order ID' => $order->id,
                            'Store Name' => $storeName,
                            'Amount' => $order->order_amount,
                            'Payment Status' => $order->payment_status,
                            'Order Status' => $order->order_status,
                            'Date' => $order->created_at->format('d M Y')
                        ];
                    });
                }
                public function headings(): array
                {
                    return ['Order ID', 'Store Name', 'Amount', 'Payment Status', 'Order Status', 'Date'];
                }
            }, 'sales_report.xlsx');

        })->name('report.download');

        // Team
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');

        // Performance
        Route::get('/performance', function () {
            return view('sales.performance');
        })->name('performance');

    });

});
