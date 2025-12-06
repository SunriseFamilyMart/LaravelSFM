<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory; // Make sure you have this model
use App\Model\Category;
use App\Model\Product;
use App\Model\Order;
use Carbon\Carbon;
use App\Models\Purchase;
use App\Model\OrderDetail;
use Illuminate\Support\Facades\DB;
use App\Exports\CategorySalesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StockReportExport;


class InventoryController extends Controller
{
    // Inventory Dashboard
    public function index()
    {
        // ✅ Total and today's order stats
        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', Carbon::today())->count();

        // ✅ Product, category, purchase info
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalPurchases = Purchase::count();
        $totalPurchaseAmount = Purchase::sum('price');
        $totalInventoryValue = Product::sum(DB::raw('price * total_stock'));


        // ✅ Sales
        $totalSalesAmount = Order::sum('order_amount');
        $todaySalesAmount = Order::whereDate('created_at', Carbon::today())
            ->sum('order_amount');

        // ✅ Pending tasks (placeholder)
        $pendingTasks = 11;

        // ✅ Low stock products
        $lowStockProducts = Product::where('total_stock', '<', 10)
            ->orderBy('total_stock', 'asc')
            ->get();

        // ✅ Most sold product
        $mostSoldProduct = OrderDetail::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->with('product')
            ->first();

        // ✅ Most sold category (fix JSON extraction)
        $mostSoldCategory = OrderDetail::join('products', 'order_details.product_id', '=', 'products.id')
            ->select(
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(products.category_ids, "$[0].id")) as category_id'),
                DB::raw('SUM(order_details.quantity) as total_sold')
            )
            ->groupBy('category_id')
            ->orderByDesc('total_sold')
            ->first();

        $mostSoldCategoryName = null;
        if ($mostSoldCategory && $mostSoldCategory->category_id) {
            $category = Category::find($mostSoldCategory->category_id);
            $mostSoldCategoryName = $category ? $category->name : null;
        }

        return view('inventory.dashboard', compact(
            'totalOrders',
            'todayOrders',
            'totalProducts',
            'totalCategories',
            'pendingTasks',
            'lowStockProducts',
            'totalSalesAmount',
            'todaySalesAmount',
            'totalPurchases',
            'totalPurchaseAmount',
            'mostSoldProduct',
            'mostSoldCategoryName',
            'totalInventoryValue' // 👈 add this

        ));
    }

    public function categoryWiseSalesReport(Request $request)
    {
        $query = DB::table('categories')
            ->leftJoin('products', 'categories.id', '=', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(products.category_ids, "$[0].id"))'))
            ->leftJoin('order_details', 'products.id', '=', 'order_details.product_id')
            ->leftJoin('orders', 'order_details.order_id', '=', 'orders.id')
            ->leftJoin('purchases', 'products.id', '=', 'purchases.product_id')
            ->select(
                'categories.id as category_id',
                'categories.name as category_name',
                DB::raw('COALESCE(SUM(order_details.quantity), 0) as total_sold_qty'),
                DB::raw('COALESCE(SUM(order_details.price * order_details.quantity), 0) as total_sales'),
                DB::raw('COALESCE(SUM(order_details.tax_amount), 0) as total_tax'),
                DB::raw('COALESCE(SUM(order_details.discount_on_product), 0) as total_discount'),
                DB::raw('COALESCE(SUM(purchases.quantity), 0) as total_purchased_qty'),
                DB::raw('COALESCE(SUM(purchases.quantity * purchases.price), 0) as total_purchase_value')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_sales');

        // Optional search by category name
        if ($request->filled('search')) {
            $query->where('categories.name', 'like', '%' . $request->search . '%');
        }

        // Paginate results
        $reports = $query->paginate(10);

        return view('reports.category_sales', compact('reports'));
    }


    public function exportCategorySales()
    {
        $filename = 'category_sales_report_' . now()->format('Y_m_d_His') . '.xlsx';
        return Excel::download(new CategorySalesExport, $filename);
    }

    public function stockReportExport()
    {
        return Excel::download(new StockReportExport, 'stock_report.xlsx');
    }

    public function stockReport()
    {
        $products = DB::table('products')
            ->leftJoin(
                'categories',
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(products.category_ids, "$[0].id"))'),
                '=',
                'categories.id'
            )
            ->select(
                'products.name',
                'categories.name as category_name',
                'products.total_stock'
            )
            ->orderBy('products.name')
            ->get();

        return view('reports.stock', compact('products'));
    }


    public function pindex(Request $request)
    {
        $search = $request->search;

        $products = Product::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%");
        })
            ->orderBy('id', 'desc')
            ->paginate(12); // Pagination

        return view('inventory.products.index', compact('products'));
    }

    // Show single product
    public function pshow($id)
    {
        $product = Product::findOrFail($id);

        // Decode category IDs
        $categoryIds = collect(json_decode($product->category_ids, true))->pluck('id')->toArray();

        // Fetch category names
        $categories = \App\Model\Category::whereIn('id', $categoryIds)->pluck('name', 'id');

        return view('inventory.products.show', compact('product', 'categories'));
    }



}
