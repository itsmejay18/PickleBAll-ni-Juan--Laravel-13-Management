<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>{{ config('app.name', 'Pickle Ballan ni Juan') }} | Court Reservation & Management System</title>

        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/branding.png') }}">
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet">
        <link href="{{ asset('soft-ui-dashboard-main/assets/css/nucleo-icons.css') }}" rel="stylesheet">
        <link href="{{ asset('soft-ui-dashboard-main/assets/css/nucleo-svg.css') }}" rel="stylesheet">
        <link href="{{ asset('soft-ui-dashboard-main/assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
        <link id="pagestyle" href="{{ asset('soft-ui-dashboard-main/assets/css/soft-ui-dashboard.css') }}" rel="stylesheet">
        @vite('resources/js/landing-map.js')

        <style>
            :root {
                --pbj-ink: #17202a;
                --pbj-court: #18a37f;
                --pbj-lime: #a8cf45;
                --pbj-sun: #f6b73c;
                --pbj-sky: #1f8ecb;
            }

            * {
                letter-spacing: 0;
            }

            body.pbj-landing {
                color: var(--pbj-ink);
                background: #f8f9fa;
                font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            .pbj-landing .card,
            .pbj-landing .modal-content,
            .pbj-landing .navbar {
                border-radius: 8px;
            }

            .landing-hero {
                min-height: 78vh;
                padding: 7.5rem 0 4rem;
                background-image:
                    linear-gradient(90deg, rgba(14, 23, 37, 0.92) 0%, rgba(14, 23, 37, 0.76) 42%, rgba(14, 23, 37, 0.42) 100%),
                    url("{{ asset('images/671478450_122128991829155269_859094970721700938_n.jpg') }}");
                background-size: cover;
                background-position: center;
            }

            .landing-hero::after {
                position: absolute;
                inset: auto 0 0;
                height: 110px;
                content: "";
                background: linear-gradient(180deg, rgba(248, 249, 250, 0), #f8f9fa);
                pointer-events: none;
            }

            .hero-copy {
                position: relative;
                z-index: 1;
                max-width: 760px;
            }

            .brand-logo {
                width: 34px;
                height: 34px;
                object-fit: cover;
                border-radius: 8px;
            }

            .hero-brand-logo {
                display: block;
                width: clamp(82px, 12vw, 128px);
                height: clamp(82px, 12vw, 128px);
                margin-bottom: 1.25rem;
                object-fit: cover;
                border-radius: 8px;
                box-shadow: 0 18px 45px rgba(0, 0, 0, 0.25);
            }

            .hero-kicker {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem 0.75rem;
                color: #e9fff8;
                background: rgba(24, 163, 127, 0.18);
                border: 1px solid rgba(233, 255, 248, 0.18);
                border-radius: 8px;
                font-size: 0.78rem;
                font-weight: 700;
            }

            .hero-title {
                color: #fff;
                font-size: clamp(2.6rem, 7vw, 5.5rem);
                line-height: 0.96;
                font-weight: 800;
                margin: 1.25rem 0 1rem;
            }

            .hero-lead {
                max-width: 690px;
                color: rgba(255, 255, 255, 0.82);
                font-size: 1.12rem;
                line-height: 1.75;
            }

            .hero-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                margin-top: 2rem;
            }

            .hero-note {
                color: rgba(255, 255, 255, 0.7);
                font-size: 0.86rem;
                margin-top: 1rem;
            }

            .objective-strip {
                position: relative;
                z-index: 2;
                margin-top: -2.25rem;
            }

            .metric-card {
                height: 100%;
                border: 1px solid rgba(15, 23, 42, 0.04);
                box-shadow: 0 14px 34px rgba(20, 20, 43, 0.08);
            }

            .metric-card .icon {
                flex: 0 0 44px;
            }

            .section-band {
                padding: 5rem 0;
            }

            .section-band.soft {
                background: #fff;
            }

            .section-eyebrow {
                color: var(--pbj-court);
                font-size: 0.8rem;
                font-weight: 800;
                text-transform: uppercase;
            }

            .section-title {
                color: var(--pbj-ink);
                font-size: clamp(2rem, 4vw, 3rem);
                line-height: 1.12;
                font-weight: 800;
            }

            .feature-card {
                height: 100%;
                border: 1px solid rgba(15, 23, 42, 0.06);
                box-shadow: none;
                transition: transform 160ms ease, box-shadow 160ms ease;
            }

            .feature-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 18px 36px rgba(20, 20, 43, 0.08);
            }

            .feature-icon {
                width: 48px;
                height: 48px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                color: #fff;
            }

            .flow-step {
                height: 100%;
                padding: 1.25rem;
                background: #fff;
                border: 1px solid rgba(15, 23, 42, 0.06);
                border-radius: 8px;
            }

            .flow-index {
                width: 34px;
                height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                background: var(--pbj-ink);
                border-radius: 8px;
                font-weight: 800;
                font-size: 0.86rem;
            }

            .role-panel {
                min-height: 320px;
                padding: 2rem;
                background:
                    linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.84)),
                    url("{{ asset('soft-ui-dashboard-main/assets/img/curved-images/curved14.jpg') }}");
                background-size: cover;
                border: 1px solid rgba(15, 23, 42, 0.06);
                border-radius: 8px;
            }

            .role-list {
                display: grid;
                gap: 0.85rem;
                margin-top: 1.5rem;
            }

            .role-list-item {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.85rem;
                background: rgba(255, 255, 255, 0.8);
                border: 1px solid rgba(15, 23, 42, 0.06);
                border-radius: 8px;
            }

            .location-card {
                height: 100%;
                padding: 2rem;
                background: #fff;
                border: 1px solid rgba(15, 23, 42, 0.06);
                border-radius: 8px;
            }

            .location-map {
                min-height: 420px;
                height: 100%;
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 8px;
                overflow: hidden;
                z-index: 0;
            }

            .location-detail {
                display: flex;
                gap: 0.75rem;
                align-items: flex-start;
                color: #67748e;
            }

            .location-detail i {
                margin-top: 0.18rem;
                color: var(--pbj-court);
            }

            .scope-callout {
                padding: 2rem;
                color: #fff;
                background: linear-gradient(135deg, #17202a 0%, #1f8ecb 100%);
                border-radius: 8px;
            }

            .scope-list {
                display: grid;
                gap: 0.75rem;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .scope-list li {
                display: flex;
                gap: 0.65rem;
                align-items: flex-start;
            }

            .scope-list i {
                margin-top: 0.18rem;
            }

            .footer-link {
                color: #67748e;
                font-size: 0.9rem;
            }

            @media (max-width: 991.98px) {
                .landing-hero {
                    min-height: 74vh;
                    padding-top: 6.75rem;
                }

                .hero-title {
                    font-size: clamp(2.45rem, 11vw, 4.15rem);
                }

                .section-band {
                    padding: 3.5rem 0;
                }
            }

            @media (max-width: 575.98px) {
                .hero-actions .btn {
                    width: 100%;
                }

                .objective-strip {
                    margin-top: -1rem;
                }
            }
        </style>
    </head>

    <body class="pbj-landing">
        <div class="container position-sticky z-index-sticky top-0">
            <div class="row">
                <div class="col-12">
                    <nav class="navbar navbar-expand-lg blur blur-rounded top-0 z-index-3 shadow position-absolute mt-4 py-2 start-0 end-0 mx-3">
                        <div class="container-fluid px-3">
                            <a class="navbar-brand font-weight-bolder mb-0 d-flex align-items-center" href="{{ url('/') }}">
                                <img src="{{ asset('images/branding.png') }}" class="brand-logo me-2" alt="" aria-hidden="true">
                                <span>Pickle Ballan ni Juan</span>
                            </a>
                            <button class="navbar-toggler shadow-none ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavigation" aria-controls="landingNavigation" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon mt-2">
                                    <span class="navbar-toggler-bar bar1"></span>
                                    <span class="navbar-toggler-bar bar2"></span>
                                    <span class="navbar-toggler-bar bar3"></span>
                                </span>
                            </button>
                            <div class="collapse navbar-collapse" id="landingNavigation">
                                <ul class="navbar-nav mx-auto">
                                    <li class="nav-item">
                                        <a class="nav-link me-2" href="#features">
                                            <i class="fas fa-layer-group opacity-6 text-dark me-1"></i>
                                            Modules
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link me-2" href="#workflow">
                                            <i class="fas fa-calendar-check opacity-6 text-dark me-1"></i>
                                            Booking Flow
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link me-2" href="#roles">
                                            <i class="fas fa-users opacity-6 text-dark me-1"></i>
                                            For Teams
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link me-2" href="#location">
                                            <i class="fas fa-map-marker-alt opacity-6 text-dark me-1"></i>
                                            Location
                                        </a>
                                    </li>
                                </ul>
                                <ul class="navbar-nav">
                                    @auth
                                        <li class="nav-item">
                                            <a class="btn btn-sm bg-gradient-info mb-0" href="{{ route('dashboard') }}">
                                                <i class="fas fa-chart-pie me-1"></i>
                                                Dashboard
                                            </a>
                                        </li>
                                    @else
                                        @if (Route::has('login'))
                                            <li class="nav-item">
                                                <a class="nav-link me-2" href="{{ route('login') }}">
                                                    <i class="fas fa-key opacity-6 text-dark me-1"></i>
                                                    Sign In
                                                </a>
                                            </li>
                                        @endif

                                        @if (Route::has('register'))
                                            <li class="nav-item">
                                                <a class="btn btn-sm bg-gradient-info mb-0" href="{{ route('register') }}">
                                                    <i class="fas fa-user-plus me-1"></i>
                                                    Create Account
                                                </a>
                                            </li>
                                        @endif
                                    @endauth
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>

        <header class="landing-hero position-relative d-flex align-items-center">
            <div class="container">
                <div class="hero-copy">
                    <img src="{{ asset('images/branding.png') }}" class="hero-brand-logo" alt="Pickle Ballan ni Juan logo">
                    <span class="hero-kicker">
                        <i class="fas fa-table-tennis"></i>
                        Court reservations made simple
                    </span>
                    <h1 class="hero-title">Pickle Ballan ni Juan</h1>
                    <p class="hero-lead">
                        Book courts, reserve equipment, manage visits, and keep play moving with a fast online reservation experience for players and venue teams.
                    </p>
                    <div class="hero-actions">
                        @auth
                            <a class="btn bg-gradient-info btn-lg mb-0" href="{{ route('dashboard') }}">
                                <i class="fas fa-chart-pie me-2"></i>
                                Open Dashboard
                            </a>
                        @else
                            @if (Route::has('register'))
                                <a class="btn bg-gradient-info btn-lg mb-0" href="{{ route('register') }}">
                                    <i class="fas fa-calendar-plus me-2"></i>
                                    Start Booking
                                </a>
                            @endif

                            @if (Route::has('login'))
                                <a class="btn btn-outline-white btn-lg mb-0" href="{{ route('login') }}">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Sign In
                                </a>
                            @endif
                        @endauth

                        <a class="btn btn-outline-white btn-lg mb-0" href="#features">
                            <i class="fas fa-layer-group me-2"></i>
                            Explore Features
                        </a>
                    </div>
                    <p class="hero-note mb-0">
                        A smoother way to find available courts, prepare for your session, and keep reservations organized.
                    </p>
                </div>
            </div>
        </header>

        <section class="objective-strip">
            <div class="container">
                <div class="row g-3">
                    <div class="col-lg-3 col-sm-6">
                        <div class="card metric-card">
                            <div class="card-body p-3 d-flex align-items-center gap-3">
                                <div class="icon icon-shape bg-gradient-success shadow text-center">
                                    <i class="fas fa-user-lock text-lg opacity-10"></i>
                                </div>
                                <div>
                                    <p class="text-sm mb-0 font-weight-bold">Player accounts</p>
                                    <p class="text-xs text-secondary mb-0">Booking history and receipts</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card metric-card">
                            <div class="card-body p-3 d-flex align-items-center gap-3">
                                <div class="icon icon-shape bg-gradient-info shadow text-center">
                                    <i class="fas fa-calendar-day text-lg opacity-10"></i>
                                </div>
                                <div>
                                    <p class="text-sm mb-0 font-weight-bold">Advance reservations</p>
                                    <p class="text-xs text-secondary mb-0">Plan ahead with ease</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card metric-card">
                            <div class="card-body p-3 d-flex align-items-center gap-3">
                                <div class="icon icon-shape bg-gradient-warning shadow text-center">
                                    <i class="fas fa-wallet text-lg opacity-10"></i>
                                </div>
                                <div>
                                    <p class="text-sm mb-0 font-weight-bold">Payment support</p>
                                    <p class="text-xs text-secondary mb-0">Clear confirmation steps</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card metric-card">
                            <div class="card-body p-3 d-flex align-items-center gap-3">
                                <div class="icon icon-shape bg-gradient-dark shadow text-center">
                                    <i class="fas fa-stopwatch text-lg opacity-10"></i>
                                </div>
                                <div>
                                    <p class="text-sm mb-0 font-weight-bold">Fast check-in</p>
                                    <p class="text-xs text-secondary mb-0">Quick arrival handling</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <main>
            <section id="features" class="section-band">
                <div class="container">
                    <div class="row mb-4">
                        <div class="col-lg-7">
                            <p class="section-eyebrow mb-2">Core Modules</p>
                            <h2 class="section-title">Everything players and venue teams need for smoother court operations.</h2>
                        </div>
                        <div class="col-lg-5 d-flex align-items-end">
                            <p class="text-secondary mb-0">
                                Keep reservations, availability, equipment, payments, and daily activity organized in one place.
                            </p>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card">
                                <div class="card-body p-4">
                                    <span class="feature-icon bg-gradient-info mb-3"><i class="fas fa-map-marker-alt"></i></span>
                                    <h5>Locations and courts</h5>
                                    <p class="text-sm text-secondary mb-0">Manage branches, court photos, operating hours, maintenance closures, and map-ready location details.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card">
                                <div class="card-body p-4">
                                    <span class="feature-icon bg-gradient-success mb-3"><i class="fas fa-calendar-check"></i></span>
                                    <h5>Reservation calendar</h5>
                                    <p class="text-sm text-secondary mb-0">Show availability by court and location, prevent double-booking, hold unpaid slots, and calculate totals.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card">
                                <div class="card-body p-4">
                                    <span class="feature-icon bg-gradient-warning mb-3"><i class="fas fa-money-check-alt"></i></span>
                                    <h5>Payment confirmation</h5>
                                    <p class="text-sm text-secondary mb-0">Guide customers through payment steps and help staff confirm reservations before play time.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card">
                                <div class="card-body p-4">
                                    <span class="feature-icon bg-gradient-primary mb-3"><i class="fas fa-boxes"></i></span>
                                    <h5>Equipment inventory</h5>
                                    <p class="text-sm text-secondary mb-0">Track rackets and balls by location with reserved, damaged, lost, maintenance, and low-stock states.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card">
                                <div class="card-body p-4">
                                    <span class="feature-icon bg-gradient-danger mb-3"><i class="fas fa-clipboard-check"></i></span>
                                    <h5>Walk-in and check-in</h5>
                                    <p class="text-sm text-secondary mb-0">Let staff create walk-in bookings, verify arrivals by search, release equipment, and complete sessions.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card">
                                <div class="card-body p-4">
                                    <span class="feature-icon bg-gradient-dark mb-3"><i class="fas fa-chart-line"></i></span>
                                    <h5>Operations insights</h5>
                                    <p class="text-sm text-secondary mb-0">Review bookings, peak hours, cancellations, equipment usage, and venue activity at a glance.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="workflow" class="section-band soft">
                <div class="container">
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <p class="section-eyebrow mb-2">Booking Flow</p>
                            <h2 class="section-title">From available slot to digital receipt.</h2>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg col-md-6">
                            <div class="flow-step">
                                <span class="flow-index">1</span>
                                <h6 class="mt-3 mb-2">Choose slot</h6>
                                <p class="text-sm text-secondary mb-0">Customers filter by date, branch, court, and time.</p>
                            </div>
                        </div>
                        <div class="col-lg col-md-6">
                            <div class="flow-step">
                                <span class="flow-index">2</span>
                                <h6 class="mt-3 mb-2">Add equipment</h6>
                                <p class="text-sm text-secondary mb-0">Rackets and balls are checked against live inventory.</p>
                            </div>
                        </div>
                        <div class="col-lg col-md-6">
                            <div class="flow-step">
                                <span class="flow-index">3</span>
                                <h6 class="mt-3 mb-2">Confirm payment</h6>
                                <p class="text-sm text-secondary mb-0">Payment details are submitted for reservation confirmation.</p>
                            </div>
                        </div>
                        <div class="col-lg col-md-6">
                            <div class="flow-step">
                                <span class="flow-index">4</span>
                                <h6 class="mt-3 mb-2">Check in</h6>
                                <p class="text-sm text-secondary mb-0">Staff search by reservation, customer, court, date, or time.</p>
                            </div>
                        </div>
                        <div class="col-lg col-md-6">
                            <div class="flow-step">
                                <span class="flow-index">5</span>
                                <h6 class="mt-3 mb-2">Review</h6>
                                <p class="text-sm text-secondary mb-0">Digital receipts, ratings, and booking history stay in your account.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="roles" class="section-band">
                <div class="container">
                    <div class="row g-4 align-items-stretch">
                        <div class="col-lg-5">
                            <div class="role-panel h-100">
                                <p class="section-eyebrow mb-2">For Teams</p>
                                <h2 class="section-title">Clear workflows for players and staff.</h2>
                                <p class="text-secondary mb-0">
                                    Pickle Ballan ni Juan supports everyday booking, venue coordination, and customer service from one organized place.
                                </p>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="role-list">
                                <div class="role-list-item">
                                    <span class="feature-icon bg-gradient-dark"><i class="fas fa-user-shield"></i></span>
                                    <div>
                                        <h6 class="mb-1">Venue management</h6>
                                        <p class="text-sm text-secondary mb-0">Coordinate courts, schedules, pricing, payments, and daily venue activity from one place.</p>
                                    </div>
                                </div>
                                <div class="role-list-item">
                                    <span class="feature-icon bg-gradient-info"><i class="fas fa-user-cog"></i></span>
                                    <div>
                                        <h6 class="mb-1">Staff operations</h6>
                                        <p class="text-sm text-secondary mb-0">Handle walk-ins, arrivals, session updates, equipment releases, and customer assistance.</p>
                                    </div>
                                </div>
                                <div class="role-list-item">
                                    <span class="feature-icon bg-gradient-success"><i class="fas fa-user-check"></i></span>
                                    <div>
                                        <h6 class="mb-1">Players</h6>
                                        <p class="text-sm text-secondary mb-0">Create an account, book courts, submit payment confirmation, view receipts, cancel when allowed, and review courts.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="location" class="section-band soft">
                <div class="container">
                    <div class="row mb-4">
                        <div class="col-lg-7">
                            <p class="section-eyebrow mb-2">Location</p>
                            <h2 class="section-title">Find Pickle Ballan ni Juan on the map.</h2>
                        </div>
                        <div class="col-lg-5 d-flex align-items-end">
                            <p class="text-secondary mb-0">
                                The marker is pinned to the venue location from Google Maps so players can quickly find the court.
                            </p>
                        </div>
                    </div>
                    <div class="row g-4 align-items-stretch">
                        <div class="col-lg-5">
                            <div class="location-card">
                                <span class="feature-icon bg-gradient-info mb-3"><i class="fas fa-map-marker-alt"></i></span>
                                <h5>Pickle Ballan ni Juan</h5>
                                <div class="location-detail mt-3">
                                    <i class="fas fa-map-pin"></i>
                                    <span>Tagged venue coordinates: 6.770250, 125.211529</span>
                                </div>
                                <div class="location-detail mt-3">
                                    <i class="fas fa-route"></i>
                                    <span>Use the map to zoom, pan, and open the location in Google Maps.</span>
                                </div>
                                <a class="btn bg-gradient-info mb-0 mt-4" href="https://www.google.com/maps/search/?api=1&query=6.77025,125.2115287" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-directions me-2"></i>
                                    Open in Google Maps
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div id="locationMap" class="location-map" aria-label="Map showing Pickle Ballan ni Juan location"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section-band soft">
                <div class="container">
                    <div class="scope-callout">
                        <div class="row g-4 align-items-center">
                            <div class="col-lg-7">
                                <p class="text-sm text-white opacity-8 text-uppercase font-weight-bold mb-2">Ready to Play</p>
                                <h2 class="text-white mb-3">Reserve your court online and keep every visit easy to manage.</h2>
                                <p class="text-white opacity-8 mb-0">
                                    Pickle Ballan ni Juan brings court availability, reservations, equipment, receipts, and venue support into one simple web experience.
                                </p>
                            </div>
                            <div class="col-lg-5">
                                <ul class="scope-list">
                                    <li><i class="fas fa-check-circle text-success"></i><span>Find available courts and reserve preferred time slots.</span></li>
                                    <li><i class="fas fa-check-circle text-success"></i><span>Add equipment rentals when your game needs them.</span></li>
                                    <li><i class="fas fa-check-circle text-success"></i><span>Access reservation details and receipts from your account.</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="py-4">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="text-sm text-secondary mb-0">Pickle Ballan ni Juan - Court Reservation & Management System</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        @if (Route::has('login'))
                            <a class="footer-link me-3" href="{{ route('login') }}">Sign in</a>
                        @endif
                        @if (Route::has('register'))
                            <a class="footer-link" href="{{ route('register') }}">Create account</a>
                        @endif
                    </div>
                </div>
            </div>
        </footer>

        <script src="{{ asset('soft-ui-dashboard-main/assets/js/core/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('soft-ui-dashboard-main/assets/js/soft-ui-dashboard.min.js') }}"></script>
    </body>
</html>
