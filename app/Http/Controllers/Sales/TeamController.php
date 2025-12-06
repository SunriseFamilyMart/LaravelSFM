<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesPerson;
use App\Models\Store;
use App\Models\Order;
use App\Models\StoreVisit;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        // ADD SEARCH FILTER (optional)
        $search = $request->search;

        // Fetch sales people with optional search
        $salesPeople = SalesPerson::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%$search%");
        })->get();

        // Build summary for each sales person
        $teamSummaryArray = [];

        foreach ($salesPeople as $person) {

            $stores = Store::where('sales_person_id', $person->id)->get();
            $storeIds = $stores->pluck('id');

            $orders = Order::whereIn('store_id', $storeIds)->get();

            $visits = StoreVisit::where('sales_person_id', $person->id)->get();

            $teamSummaryArray[] = [
                'person' => $person,
                'stores' => $stores,
                'orders' => $orders,
                'visits' => $visits,
                'store_count' => $stores->count(),
                'order_count' => $orders->count(),
                'total_amount' => $orders->sum('order_amount'),
                'visit_count' => $visits->count(),
            ];
        }

        // --------- PAGINATION FOR ARRAY ---------
        $perPage = 6; // how many cards per page
        $page = LengthAwarePaginator::resolveCurrentPage();
        $collection = collect($teamSummaryArray);

        $paginatedData = new LengthAwarePaginator(
            $collection->slice(($page - 1) * $perPage, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // ----------------------------------------

        return view('sales.team.index', [
            'teamSummary' => $paginatedData
        ]);
    }
}