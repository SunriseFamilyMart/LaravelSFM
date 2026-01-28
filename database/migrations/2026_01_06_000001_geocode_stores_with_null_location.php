<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration geocodes stores that have NULL latitude/longitude.
     */
    public function up(): void
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        
        if (!$apiKey) {
            echo "Warning: GOOGLE_MAPS_API_KEY not set. Skipping geocoding.\n";
            return;
        }

        // Get all stores with NULL or 0 latitude/longitude
        $stores = DB::table('stores')
            ->where(function ($query) {
                $query->whereNull('latitude')
                    ->orWhereNull('longitude')
                    ->orWhere('latitude', 0)
                    ->orWhere('longitude', 0);
            })
            ->whereNotNull('address')
            ->get();

        echo "Found " . count($stores) . " stores with missing location data.\n";

        foreach ($stores as $store) {
            if (empty($store->address)) {
                echo "Store #{$store->id}: No address, skipping.\n";
                continue;
            }

            try {
                $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $store->address,
                    'key' => $apiKey,
                ]);

                $geoData = $response->json();

                if (!empty($geoData['results'][0]['geometry']['location'])) {
                    $lat = $geoData['results'][0]['geometry']['location']['lat'];
                    $lng = $geoData['results'][0]['geometry']['location']['lng'];

                    DB::table('stores')
                        ->where('id', $store->id)
                        ->update([
                            'latitude' => $lat,
                            'longitude' => $lng,
                            'updated_at' => now(),
                        ]);

                    echo "Store #{$store->id} ({$store->store_name}): Updated to ({$lat}, {$lng})\n";
                } else {
                    echo "Store #{$store->id} ({$store->store_name}): Geocoding failed - no results\n";
                }

                // Rate limit: sleep 200ms between requests
                usleep(200000);

            } catch (\Exception $e) {
                echo "Store #{$store->id}: Error - " . $e->getMessage() . "\n";
            }
        }

        echo "Geocoding migration completed.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse geocoding - coordinates are valid data
        // No action needed
    }
};
