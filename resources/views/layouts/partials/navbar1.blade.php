 <header id="page-topbar">

    <div class="navbar-left">

        <button
            type="button"
            class="sidebar-toggle"
            id="sidebarToggle"
            aria-label="Toggle sidebar"
        >
            <i class="bi bi-list"></i>
        </button>

        <div class="navbar-system-name">
            LAPF Pension Fund System
        </div>

    </div>


    <div class="navbar-right">

        @auth

            <div class="user-details">

                <span class="user-name">
                    {{ auth()->user()->full_name }}
                </span>

                <span class="user-position">
                    {{ auth()->user()->jobTitle?->name ?? 'System User' }}
                </span>

            </div>


            <div class="navbar-user-icon">
                <i class="bi bi-person-circle"></i>
            </div>


            <form
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf

                <button
                    type="submit"
                    class="logout-button"
                >
                    <i class="bi bi-box-arrow-right"></i>

                    <span>Logout</span>
                </button>
            </form>

        @endauth

    </div>

</header>