<div class="mb-3">
    <label>Name</label>
    <input type="text" name="name" value="{{ old('name', $inventory->name ?? '') }}" class="form-control" required>
</div>

<div class="mb-3">
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email', $inventory->email ?? '') }}" class="form-control" required>
</div>

<div class="mb-3">
    <label>Phone</label>
    <input type="text" name="phone" value="{{ old('phone', $inventory->phone ?? '') }}" class="form-control">
</div>

@if (!isset($inventory))
    <div class="mb-3 position-relative">
        <label>Password</label>
        <div class="input-group">
            <input type="password" name="password" id="password" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                <i class="tio-hidden"></i> {{-- eye icon --}}
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const icon = toggle.querySelector('i');

            toggle.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

                // Toggle icon class
                icon.classList.toggle('tio-hidden');
                icon.classList.toggle('tio-visible');
            });
        });
    </script>
@endif


<div class="mb-3">
    <label>Status</label>
    <select name="status" class="form-control" required>
        <option value="active" {{ old('status', $inventory->status ?? '') == 'active' ? 'selected' : '' }}>Active
        </option>
        <option value="inactive" {{ old('status', $inventory->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive
        </option>
    </select>
</div>

<div class="mb-3">
    <label>ID Proof (optional)</label>
    <input type="file" name="idproof" class="form-control">
    @if (!empty($inventory->idproof))
        <a href="{{ asset('storage/' . $inventory->idproof) }}" target="_blank" class="d-block mt-2">View current</a>
    @endif
</div>
