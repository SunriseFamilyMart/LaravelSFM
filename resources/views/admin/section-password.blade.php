<div class="container mt-5">
    <div class="card shadow mx-auto" style="max-width: 400px;">
        <div class="card-body text-center">
            <h4 class="mb-3 text-capitalize">{{ $section }} Section Password</h4>
            <form method="POST" action="{{ route('admin.section.password.verify', $section) }}">
                @csrf
                <div class="form-group mb-3">
                    <input type="password" name="password" class="form-control text-center" placeholder="Enter Password"
                        required>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-100">Unlock</button>
            </form>
        </div>
    </div>
</div>
