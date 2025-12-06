<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\SalesPerson;
use App\Models\StoreVisit;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class StoreVisitController extends Controller
{
    public function store(Request $request)
    {
        // Check Token from header
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json(['error' => 'Token missing'], 401);
        }

        // Validate Sales Person
        $salesPerson = SalesPerson::where('auth_token', $bearerToken)->first();

        if (!$salesPerson) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate input
        $request->validate([
            'store_id' => 'required|integer',
            'photo' => 'required|image|max:2048',
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
            'address' => 'required|string',
        ]);

        // Upload image
        $path = $request->file('photo')->store('store_visits', 'public');

        // Insert into DB
        $storeVisit = StoreVisit::create([
            'store_id' => $request->store_id,
            'sales_person_id' => $salesPerson->id,
            'photo' => $path,
            'lat' => $request->lat,
            'long' => $request->long,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Store visit recorded successfully',
            'data' => $storeVisit
        ]);
    }

    public function index(Request $request)
    {
        // Check Token from header
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json(['error' => 'Token missing'], 401);
        }

        // Validate Sales Person
        $salesPerson = SalesPerson::where('auth_token', $bearerToken)->first();

        if (!$salesPerson) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Fetch only today's store visits
        $storeVisits = StoreVisit::with('store')
            ->where('sales_person_id', $salesPerson->id)
            ->whereDate('created_at', Carbon::today())  // <-- today's date filter
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Today\'s store visits fetched successfully',
            'data' => $storeVisits
        ]);
    }

}
