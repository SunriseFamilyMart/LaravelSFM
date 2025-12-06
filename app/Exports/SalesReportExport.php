<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;

class SalesReportExport implements FromView
{
    public function view(): View
    {
        $salesData = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->join('categories', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(products.category_ids, "$[0].id"))'), '=', 'categories.id')
            ->leftJoin('sales_people', 'orders.sales_person_id', '=', 'sales_people.id') // ✅ Correct join table
            ->leftJoin('users', 'orders.user_id', '=', 'users.id') // ✅ For customer name
            ->select(
                DB::raw('COALESCE(sales_people.name, users.f_name) as salesperson_name'), // ✅ Use salesperson name, else customer
                'categories.name as category_name',
                'products.name as product_name',
                DB::raw('SUM(order_details.quantity) as total_quantity'),
                DB::raw('SUM(order_details.price * order_details.quantity) as total_amount')
            )
            ->groupBy('salesperson_name', 'categories.name', 'products.name')
            ->get();

        return view('admin-views.report.sales-excel', [
            'salesData' => $salesData
        ]);
    }
}