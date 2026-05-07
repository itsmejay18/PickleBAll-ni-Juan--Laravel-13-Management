<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Pickle Ball ni Juan') }} | Court Reservation & Management System</title>

        <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('soft-ui-dashboard-main/assets/img/apple-icon.png') }}">
        <link rel="icon" type="image/png" href="{{ asset('soft-ui-dashboard-main/assets/img/favicon.png') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet">
        <link href="{{ asset('soft-ui-dashboard-main/assets/css/nucleo-icons.css') }}" rel="stylesheet">
        <link href="{{ asset('soft-ui-dashboard-main/assets/css/nucleo-svg.css') }}" rel="stylesheet">
        <link href="{{ asset('soft-ui-dashboard-main/assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
        <link id="pagestyle" href="{{ asset('soft-ui-dashboard-main/assets/css/soft-ui-dashboard.css') }}" rel="stylesheet">

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
                    url("{{ asset('soft-ui-dashboard-main/media/soft-ui-dashboard-screen.png') }}");
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
                            <a class="navbar-brand font-weight-bolder mb-0" href="{{ url('/') }}">
                                Pickle Ball ni Juan
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
                                            Roles
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
                    <span class="hero-kicker">
                        <i class="fas fa-table-tennis"></i>
                        Laravel court reservation and management system
                    </span>
                    <h1 class="hero-title">Pickle Ball ni Juan</h1>
                    <p class="hero-lead">
                        A web-based system for online court reservations, equipment rentals, manual GCash payment confirmation, walk-in bookings, staff check-in, digital receipts, and admin reporting across multiple locations.
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
                                    Staff or Admin Login
                                </a>
                            @endif
                        @endauth

                        <button type="button" class="btn btn-outline-white btn-lg mb-0" data-bs-toggle="modal" data-bs-target="#scopeModal">
                            <i class="fas fa-tasks me-2"></i>
                            Phase 1 Scope
                        </button>
                    </div>
                    <p class="hero-note mb-0">
                        Built around the approved objectives: no guest bookings, RBAC, manual GCash verification, digital receipts, staff search check-in, and audit-ready reporting.
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
                                    <p class="text-sm mb-0 font-weight-bold">Mandatory accounts</p>
                                    <p class="text-xs text-secondary mb-0">A1, A4, P1</p>
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
                                    <p class="text-sm mb-0 font-weight-bold">30-day booking window</p>
                                    <p class="text-xs text-secondary mb-0">E1, E4, E8</p>
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
                                    <p class="text-sm mb-0 font-weight-bold">Manual GCash proof</p>
                                    <p class="text-xs text-secondary mb-0">F1-F11</p>
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
                                    <p class="text-sm mb-0 font-weight-bold">Under 1 minute check-in</p>
                                    <p class="text-xs text-secondary mb-0">I1-I5, success metric</p>
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
                            <h2 class="section-title">Everything the objectives require for court operations.</h2>
                        </div>
                        <div class="col-lg-5 d-flex align-items-end">
                            <p class="text-secondary mb-0">
                                The public landing page now reflects the same modules already mapped in the dashboard shell and database plan.
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
                                    <h5>Manual GCash verification</h5>
                                    <p class="text-sm text-secondary mb-0">Collect payment screenshots, reference numbers, staff confirmation, rejection reasons, and audit history.</p>
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
                                    <h5>Reports and audit</h5>
                                    <p class="text-sm text-secondary mb-0">Monitor revenue, bookings, peak hours, cancellations, equipment usage, utilization, and system activity.</p>
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
                                <h6 class="mt-3 mb-2">Submit proof</h6>
                                <p class="text-sm text-secondary mb-0">GCash screenshot and reference number go to verification.</p>
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
                                <p class="text-sm text-secondary mb-0">Digital receipts, ratings, and history stay in the dashboard.</p>
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
                                <p class="section-eyebrow mb-2">Role-Based Access</p>
                                <h2 class="section-title">Five roles, distinct permissions.</h2>
                                <p class="text-secondary mb-0">
                                    The system is designed for Super Admin, Admin, Location Manager, Staff, and End User workflows with permission-based access across every action.
                                </p>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="role-list">
                                <div class="role-list-item">
                                    <span class="feature-icon bg-gradient-dark"><i class="fas fa-user-shield"></i></span>
                                    <div>
                                        <h6 class="mb-1">Super Admin and Admin</h6>
                                        <p class="text-sm text-secondary mb-0">Manage locations, courts, pricing, users, payment verification, reports, settings, and audit logs.</p>
                                    </div>
                                </div>
                                <div class="role-list-item">
                                    <span class="feature-icon bg-gradient-info"><i class="fas fa-user-cog"></i></span>
                                    <div>
                                        <h6 class="mb-1">Location Manager and Staff</h6>
                                        <p class="text-sm text-secondary mb-0">Run daily schedules, walk-ins, GCash checks, customer check-in/out, and equipment return handling.</p>
                                    </div>
                                </div>
                                <div class="role-list-item">
                                    <span class="feature-icon bg-gradient-success"><i class="fas fa-user-check"></i></span>
                                    <div>
                                        <h6 class="mb-1">End User</h6>
                                        <p class="text-sm text-secondary mb-0">Create an account, book courts, upload payment proof, view receipts, cancel when allowed, and review courts.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section-band soft">
                <div class="container">
                    <div class="scope-callout">
                        <div class="row g-4 align-items-center">
                            <div class="col-lg-7">
                                <p class="text-sm text-white opacity-8 text-uppercase font-weight-bold mb-2">SMART Objective</p>
                                <h2 class="text-white mb-3">Deliver a Laravel reservation system that cuts booking errors to zero and check-in time under 1 minute.</h2>
                                <p class="text-white opacity-8 mb-0">
                                    Phase 1 focuses on web-based booking, manual payment verification, digital receipts, walk-ins, equipment inventory, ratings, RBAC, and reporting.
                                </p>
                            </div>
                            <div class="col-lg-5">
                                <ul class="scope-list">
                                    <li><i class="fas fa-check-circle text-success"></i><span>Manual GCash and cash payments, no payment API dependency.</span></li>
                                    <li><i class="fas fa-check-circle text-success"></i><span>No QR check-in; staff validates reservations by search.</span></li>
                                    <li><i class="fas fa-check-circle text-success"></i><span>Mobile responsive web app for current Phase 1 scope.</span></li>
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
                        <p class="text-sm text-secondary mb-0">Pickle Ball ni Juan - Court Reservation & Management System</p>
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

        <div class="modal fade" id="scopeModal" tabindex="-1" aria-labelledby="scopeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <p class="section-eyebrow mb-1">Phase 1 Scope</p>
                            <h5 class="modal-title" id="scopeModalLabel">Approved objective groups</h5>
                        </div>
                        <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <ul class="list-group">
                                    <li class="list-group-item border-0 ps-0 text-sm"><strong>A-P:</strong> Users, roles, courts, pricing, rentals, calendar, payments, receipts, walk-ins, check-in/out, cancellations, ratings, reporting, and security.</li>
                                    <li class="list-group-item border-0 ps-0 text-sm"><strong>Targets:</strong> zero double-booking incidents, under 1 minute average check-in, over 80% online booking completion, and 99% uptime during operating hours.</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group">
                                    <li class="list-group-item border-0 ps-0 text-sm"><strong>Included payments:</strong> GCash proof upload, staff verification, walk-in cash payments, audit trail, and partial refunds by policy.</li>
                                    <li class="list-group-item border-0 ps-0 text-sm"><strong>Out of scope:</strong> automatic GCash API, QR scanning, native mobile app, card payments, tournaments, chat, POS, and accounting integrations.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @auth
                            <a class="btn bg-gradient-info mb-0" href="{{ route('dashboard') }}">
                                <i class="fas fa-chart-pie me-2"></i>
                                Open Dashboard
                            </a>
                        @else
                            @if (Route::has('register'))
                                <a class="btn bg-gradient-info mb-0" href="{{ route('register') }}">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Create Account
                                </a>
                            @endif
                        @endauth
                        <button type="button" class="btn bg-gradient-secondary mb-0" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('soft-ui-dashboard-main/assets/js/core/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('soft-ui-dashboard-main/assets/js/soft-ui-dashboard.min.js') }}"></script>
    </body>
</html>
