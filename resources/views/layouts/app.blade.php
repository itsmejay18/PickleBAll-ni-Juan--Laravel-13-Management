@php
    $user = Auth::user();
    $roleLabel = $user?->roles->pluck('name')->map(fn ($role) => str_replace('_', ' ', $role))->implode(', ') ?: 'End User';
    $dashboardActive = request()->routeIs('dashboard');
    $profileActive = request()->routeIs('profile.*');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Pickle Ball ni Juan') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="g-sidenav-show bg-gray-100">
        <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-white" id="sidenav-main">
            <div class="sidenav-header">
                <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-xl-none" aria-hidden="true" id="iconSidenav"></i>
                <a class="navbar-brand m-0" href="{{ route('dashboard') }}">
                    <img src="{{ asset('soft-ui-dashboard-main/assets/img/logo-ct-dark.png') }}" class="navbar-brand-img h-100" alt="Pickle Ball ni Juan">
                    <span class="ms-1 font-weight-bold">Pickle Ball ni Juan</span>
                </a>
            </div>

            <hr class="horizontal dark mt-0">

            <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link pbj-sidebar-link {{ $dashboardActive ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                                <i class="fas fa-chart-pie text-sm text-dark"></i>
                            </div>
                            <span class="nav-link-text ms-1">Dashboard</span>
                        </a>
                    </li>

                    @role('super_admin|admin')
                        <li class="nav-item mt-3">
                            <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Admin</h6>
                        </li>
                        <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-location-dot text-sm me-3 ms-2"></i> Locations</a></li>
                        <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-table-tennis-paddle-ball text-sm me-3 ms-2"></i> Courts</a></li>
                        <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-money-check-dollar text-sm me-3 ms-2"></i> Payment Verification</a></li>
                        <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-boxes-stacked text-sm me-3 ms-2"></i> Equipment</a></li>
                        <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-file-export text-sm me-3 ms-2"></i> Reports</a></li>
                    @endrole

                    @role('location_manager|staff')
                        <li class="nav-item mt-3">
                            <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Staff</h6>
                        </li>
                        <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-person-walking text-sm me-3 ms-2"></i> Walk-in Booking</a></li>
                        <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-clipboard-check text-sm me-3 ms-2"></i> Check-in</a></li>
                        <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-arrow-right-from-bracket text-sm me-3 ms-2"></i> Check-out</a></li>
                    @endrole

                    <li class="nav-item mt-3">
                        <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Customer</h6>
                    </li>
                    <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-calendar-plus text-sm me-3 ms-2"></i> Book Court</a></li>
                    <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-receipt text-sm me-3 ms-2"></i> Receipts</a></li>
                    <li class="nav-item"><a class="nav-link pbj-sidebar-link" href="#"><i class="fas fa-star text-sm me-3 ms-2"></i> Reviews</a></li>

                    <li class="nav-item mt-3">
                        <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Account</h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link pbj-sidebar-link {{ $profileActive ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                            <i class="fas fa-user text-sm me-3 ms-2"></i> Profile
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
            <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
                <div class="container-fluid py-1 px-3">
                    <nav aria-label="breadcrumb">
                        <h6 class="font-weight-bolder mb-0">{{ $header ?? 'Dashboard' }}</h6>
                        <p class="text-xs text-secondary mb-0 text-capitalize">{{ $roleLabel }}</p>
                    </nav>
                    <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4">
                        <ul class="navbar-nav justify-content-end ms-auto">
                            <li class="nav-item d-flex align-items-center me-3">
                                <span class="nav-link text-body font-weight-bold px-0">{{ $user?->email }}</span>
                            </li>
                            <li class="nav-item d-flex align-items-center">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm bg-gradient-dark mb-0">Log out</button>
                                </form>
                            </li>
                            <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                                <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                                    <div class="sidenav-toggler-inner">
                                        <i class="sidenav-toggler-line"></i>
                                        <i class="sidenav-toggler-line"></i>
                                        <i class="sidenav-toggler-line"></i>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid py-4">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
