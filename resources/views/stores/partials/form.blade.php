<div class="card shadow-sm p-4">
    <div class="row g-3">

        {{-- Store Name --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">Store Name <span class="text-danger">*</span></label>
            <input type="text" name="store_name" value="{{ old('store_name', $store->store_name ?? '') }}"
                class="form-control" required>
        </div>

        {{-- Customer Name --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">Customer Name <span class="text-danger">*</span></label>
            <input type="text" name="customer_name" value="{{ old('customer_name', $store->customer_name ?? '') }}"
                class="form-control" required>
        </div>

        {{-- Address --}}
        <div class="col-12">
            <label class="form-label fw-bold">Address</label>
            <textarea name="address" class="form-control" rows="3">{{ old('address', $store->address ?? '') }}</textarea>
        </div>

        {{-- Phone Number --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
            <input type="text" name="phone_number" value="{{ old('phone_number', $store->phone_number ?? '') }}"
                class="form-control" required>
        </div>

        {{-- Alternate Number --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">Alternate Number</label>
            <input type="text" name="alternate_number"
                value="{{ old('alternate_number', $store->alternate_number ?? '') }}" class="form-control">
        </div>

        {{-- Landmark --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">Landmark</label>
            <input type="text" name="landmark" value="{{ old('landmark', $store->landmark ?? '') }}"
                class="form-control">
        </div>

        {{-- Branch --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">Branch</label>
            <select name="branch" class="form-control">
                <option value="">Select Branch</option>
                @foreach(\App\Model\Branch::all() as $branch)
                    <option value="{{ $branch->name }}" 
                        {{ old('branch', $store->branch ?? '') == $branch->name ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="gst_number" class="form-label">GST Number</label>
            <input type="text" name="gst_number" id="gst_number" class="form-control"
                value="{{ old('gst_number', $store->gst_number ?? '') }}" placeholder="Enter GST Number">
        </div>


        {{-- Latitude --}}
        {{-- Latitude --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">Latitude</label>
            <input type="text" id="latitude" name="latitude" value="{{ old('latitude', $store->latitude ?? '') }}"
                class="form-control" readonly>
        </div>

        {{-- Longitude --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">Longitude</label>
            <input type="text" id="longitude" name="longitude"
                value="{{ old('longitude', $store->longitude ?? '') }}" class="form-control" readonly>
        </div>

        {{-- Google Map with Search --}}
        <div class="col-12 mt-3">
            <label class="form-label fw-bold">Select Location</label>

            {{-- Search box --}}
            <input id="pac-input" class="form-control mb-2" type="text" placeholder="Search for a place">

            {{-- Map --}}
            <div id="map" style="height: 400px; border: 1px solid #ddd;"></div>
        </div>



        {{-- Store Photo --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">Store Photo</label>
            <input type="file" name="store_photo" class="form-control">

            @if (!empty($store->store_photo))
                <div class="mt-2">
                    <img src="{{ asset('storage/' . $store->store_photo) }}" alt="store photo" class="img-thumbnail"
                        width="150">
                </div>
            @endif
        </div>

    </div>
</div>
<script>
    let map, marker, autocomplete;

    function initMap() {
        // Default location (Bangalore) if no lat/long
        let defaultLocation = {
            lat: parseFloat(document.getElementById("latitude").value) || 12.9716,
            lng: parseFloat(document.getElementById("longitude").value) || 77.5946
        };

        map = new google.maps.Map(document.getElementById("map"), {
            center: defaultLocation,
            zoom: 13
        });

        marker = new google.maps.Marker({
            position: defaultLocation,
            map: map,
            draggable: true
        });

        // Search input
        const input = document.getElementById("pac-input");
        autocomplete = new google.maps.places.Autocomplete(input);
        autocomplete.bindTo("bounds", map);

        // When a place is selected
        autocomplete.addListener("place_changed", function() {
            const place = autocomplete.getPlace();

            if (!place.geometry || !place.geometry.location) {
                alert("No details available for: '" + place.name + "'");
                return;
            }

            // Center map on selected place
            map.setCenter(place.geometry.location);
            map.setZoom(15);

            // Move marker
            marker.setPosition(place.geometry.location);

            // Update lat/long fields
            document.getElementById("latitude").value = place.geometry.location.lat().toFixed(7);
            document.getElementById("longitude").value = place.geometry.location.lng().toFixed(7);
        });

        // Update lat/long fields when marker dragged
        marker.addListener("dragend", function(event) {
            document.getElementById("latitude").value = event.latLng.lat().toFixed(7);
            document.getElementById("longitude").value = event.latLng.lng().toFixed(7);
        });

        // Update marker on map click
        map.addListener("click", function(event) {
            marker.setPosition(event.latLng);
            document.getElementById("latitude").value = event.latLng.lat().toFixed(7);
            document.getElementById("longitude").value = event.latLng.lng().toFixed(7);
        });
    }
</script>

{{-- Google Maps JS API with Places library --}}
<script async src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places&callback=initMap"></script>
