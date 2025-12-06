<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CategorySalesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        $data = DB::table('categories')
            ->leftJoin('products', 'categories.id', '=', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(products.category_ids, "$[0].id"))'))
            ->leftJoin('order_details', 'products.id', '=', 'order_details.product_id')
            ->leftJoin('orders', 'order_details.order_id', '=', 'orders.id')
            ->leftJoin('purchases', 'products.id', '=', 'purchases.product_id')
            ->select(
                'categories.name as Category',
                DB::raw('COALESCE(SUM(purchases.quantity), 0) as Total_Purchased_Qty'),
                DB::raw('COALESCE(SUM(purchases.quantity * purchases.price), 0) as Total_Purchase_Value'),
                DB::raw('COALESCE(SUM(order_details.quantity), 0) as Total_Sold_Qty'),
                DB::raw('COALESCE(SUM(order_details.price * order_details.quantity), 0) as Total_Sales'),
                DB::raw('COALESCE(SUM(order_details.tax_amount), 0) as Total_Tax'),
                DB::raw('COALESCE(SUM(order_details.discount_on_product), 0) as Total_Discount')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('Total_Sales')
            ->get();

        // Add computed profit/loss for each category
        return $data->map(function ($item) {
            $item->Profit_Loss = $item->Total_Sales - $item->Total_Purchase_Value;
            return $item;
        });
    }

    public function headings(): array
    {
        return [
            'Category',
            'Total Purchased Qty',
            'Total Purchase Value (₹)',
            'Total Sold Qty',
            'Total Sales (₹)',
            'Total Tax (₹)',
            'Total Discount (₹)',
            'Profit / Loss (₹)',
        ];
    }
}
