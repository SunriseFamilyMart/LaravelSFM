 <!-- Header -->
            <nav class="navbar navbar-expand-lg navbar-light navbar-custom px-4">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h1">{{ translate('Inventory Dashboard') }}</span>
                    <div class="d-flex ms-auto">
                        <form method="POST" action="{{ route('inventory.auth.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-danger">
                                {{ translate('Logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </nav>