<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        return DB::table('products')
            ->leftJoin('categories', 
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(products.category_ids, "$[0].id"))'),
                '=',
                'categories.id'
            )
            ->select(
                'products.name as Product',
                'categories.name as Category',
                'products.total_stock as Total_Stock'
            )
            ->orderBy('products.name')
            ->get();
    }

    public function headings(): array
    {
        return ['Product', 'Category', 'Total Stock'];
    }
}
