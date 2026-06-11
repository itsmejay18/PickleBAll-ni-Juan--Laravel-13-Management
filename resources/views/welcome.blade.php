<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pickle Ballan ni Juan — Book a Court</title>

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('soft-ui-dashboard-main/assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:   #18a37f;
            --ink:     #0d1117;
            --white:   #ffffff;
            --muted:   rgba(255,255,255,0.62);
            --radius:  14px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--ink);
            color: var(--white);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ─── NAV ─── */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 2rem;
            background: rgba(13, 17, 23, 0.72);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            color: var(--white);
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.02em;
        }

        .nav-brand img {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            object-fit: cover;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.18);
            color: var(--white);
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 160ms, border-color 160ms;
        }

        .btn-ghost:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.32);
            color: var(--white);
        }

        .btn-primary {
            background: var(--green);
            border: 1px solid var(--green);
            color: var(--white);
            padding: 0.5rem 1.4rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 160ms;
        }

        .btn-primary:hover { opacity: 0.88; color: var(--white); }

        /* ─── HERO ─── */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 7rem 2rem 5rem;

            background-image:
                linear-gradient(
                    to bottom,
                    rgba(13,17,23,0.55) 0%,
                    rgba(13,17,23,0.72) 60%,
                    rgba(13,17,23,0.95) 100%
                ),
                url("{{ asset('images/671478450_122128991829155269_859094970721700938_n.jpg') }}");
            background-size: cover;
            background-position: center;
        }

        .hero-inner {
            max-width: 680px;
            margin: 0 auto;
            text-align: center;
        }

        .hero-logo {
            width: clamp(72px, 12vw, 100px);
            height: clamp(72px, 12vw, 100px);
            border-radius: var(--radius);
            object-fit: cover;
            box-shadow: 0 20px 50px rgba(0,0,0,0.35);
            margin-bottom: 1.75rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.85rem;
            background: rgba(24,163,127,0.15);
            border: 1px solid rgba(24,163,127,0.35);
            border-radius: 100px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #4ecca3;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }

        .hero-title {
            font-size: clamp(2.4rem, 7vw, 4.5rem);
            font-weight: 800;
            line-height: 1.0;
            letter-spacing: -0.03em;
            color: var(--white);
            margin-bottom: 1.1rem;
        }

        .hero-title span {
            color: var(--green);
        }

        .hero-sub {
            font-size: 1.05rem;
            color: var(--muted);
            line-height: 1.7;
            margin-bottom: 2.25rem;
            font-weight: 400;
        }

        .hero-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }

        .btn-hero-primary {
            background: var(--green);
            color: var(--white);
            padding: 0.85rem 2rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 160ms, transform 160ms;
            border: none;
        }

        .btn-hero-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            color: var(--white);
        }

        .btn-hero-ghost {
            background: rgba(255,255,255,0.08);
            color: var(--white);
            padding: 0.85rem 2rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(255,255,255,0.18);
            transition: background 160ms, transform 160ms;
        }

        .btn-hero-ghost:hover {
            background: rgba(255,255,255,0.14);
            transform: translateY(-2px);
            color: var(--white);
        }

        /* ─── STEPS SECTION ─── */
        .steps-section {
            padding: 5rem 2rem;
            background: #0d1117;
        }

        .section-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--green);
            margin-bottom: 0.6rem;
        }

        .section-heading {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--white);
            margin-bottom: 0.5rem;
        }

        .section-sub {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: var(--radius);
            overflow: hidden;
            margin-top: 3rem;
        }

        .step-item {
            background: #111827;
            padding: 2rem 1.75rem;
            transition: background 160ms;
        }

        .step-item:hover { background: #151e2b; }

        .step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--green);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 1.1rem;
        }

        .step-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 0.5rem;
        }

        .step-desc {
            font-size: 0.875rem;
            color: var(--muted);
            line-height: 1.6;
        }

        /* ─── PILLS ROW ─── */
        .pills-section {
            padding: 3rem 2rem;
            background: #080c12;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            align-items: center;
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 100px;
            font-size: 0.82rem;
            font-weight: 500;
            color: rgba(255,255,255,0.75);
        }

        .pill i { color: var(--green); font-size: 0.78rem; }

        /* ─── CTA ─── */
        .cta-section {
            padding: 6rem 2rem;
            text-align: center;
            background: #0d1117;
        }

        .cta-box {
            max-width: 560px;
            margin: 0 auto;
        }

        .cta-box .hero-title {
            font-size: clamp(2rem, 5vw, 3.2rem);
            margin-bottom: 1rem;
        }

        .cta-box .hero-sub { margin-bottom: 2rem; }

        /* ─── FOOTER ─── */
        footer {
            padding: 1.75rem 2rem;
            background: #080c12;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .footer-copy {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.35);
        }

        .footer-links {
            display: flex;
            gap: 1rem;
        }

        .footer-links a {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.45);
            text-decoration: none;
            transition: color 140ms;
        }

        .footer-links a:hover { color: var(--white); }

        /* ─── LOCATIONS ─── */
        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .location-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: var(--radius);
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: transform 160ms, border-color 160ms, box-shadow 160ms;
        }

        .location-card:hover {
            transform: translateY(-4px);
            border-color: rgba(24, 163, 127, 0.3);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
        }

        .location-card-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .location-card-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .location-card-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--white);
        }

        .location-card-address {
            font-size: 0.85rem;
            color: var(--muted);
            line-height: 1.5;
        }

        .location-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .location-card-actions .btn-ghost,
        .location-card-actions .btn-primary {
            flex: 1;
            text-align: center;
        }

        /* ─── SLOTS AVAILABILITY GRID ─── */
        .slots-timeline {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 0.65rem;
            width: 100%;
            margin-top: 0.5rem;
        }

        .slot-badge {
            background: rgba(24, 163, 127, 0.04);
            border: 1px solid rgba(24, 163, 127, 0.2);
            color: var(--white);
            cursor: pointer;
            padding: 0.75rem 0.6rem;
            border-radius: 10px;
            font-size: 0.78rem;
            font-weight: 500;
            text-align: center;
            transition: all 180ms ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.45rem;
            width: 100%;
        }

        .slot-badge.slot-hover:hover {
            background: rgba(24, 163, 127, 0.16) !important;
            border-color: var(--green) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 163, 127, 0.18);
        }

        .slot-badge.slot-unavailable {
            background: rgba(245, 54, 92, 0.03) !important;
            border: 1px solid rgba(245, 54, 92, 0.1) !important;
            color: rgba(255, 255, 255, 0.3) !important;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .slot-badge .slot-label {
            color: var(--muted);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .slot-badge.slot-unavailable .slot-label {
            text-decoration: line-through;
            opacity: 0.6;
        }

        .slot-badge .btn-slot-action {
            padding: 0.3rem 0.6rem;
            font-size: 0.65rem;
            border-radius: 6px;
            font-weight: 700;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            margin: 0;
            transition: all 150ms ease;
            border: none;
        }

        .slot-badge .btn-slot-action.btn-book {
            background: var(--green);
            color: var(--white);
        }

        .slot-badge .btn-slot-action.btn-blocked {
            background: rgba(245, 54, 92, 0.08);
            color: #f5365c;
            border: 1px solid rgba(245, 54, 92, 0.18);
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 600px) {
            nav { padding: 1rem 1.25rem; }
            .nav-brand span { display: none; }
            .hero { padding: 6rem 1.25rem 4rem; }
            .steps-section, .cta-section { padding: 3.5rem 1.25rem; }
            .steps-grid { grid-template-columns: 1fr; }
            footer { flex-direction: column; text-align: center; }
            .footer-links { justify-content: center; }
        }
    </style>
</head>
<body>

    {{-- ─── NAV ─── --}}
    <nav>
        <a href="{{ url('/') }}" class="nav-brand">
            <img src="{{ asset('images/branding.png') }}" alt="Pickle Ballan ni Juan">
            <span>Pickle Ballan ni Juan</span>
        </a>
        <div class="nav-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn-ghost">Sign In</a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-primary">Book a Court</a>
                @endif
            @endauth
        </div>
    </nav>

    {{-- ─── HERO ─── --}}
    <section class="hero">
        <div class="hero-inner">
            <img src="{{ asset('images/branding.png') }}" class="hero-logo" alt="Pickle Ballan ni Juan">

            <div class="hero-badge">
                <i class="fas fa-circle" style="font-size:0.45rem;"></i>
                Pickleball Court Reservations
            </div>

            <h1 class="hero-title">
                Play more.<br>
                <span>Book in minutes.</span>
            </h1>

            <p class="hero-sub">
                Reserve your pickleball court, rent equipment, and pay via GCash — all in one place. No calls, no waiting.
            </p>

            <div class="hero-btns" style="margin-bottom: 2rem;">
                @auth
                    <a href="{{ route('modules.show', ['module' => 'book-court']) }}" class="btn-hero-primary">
                        <i class="fas fa-calendar-plus"></i> Reserve a Court
                    </a>
                    <a href="#availability" class="btn-hero-ghost">
                        <i class="fas fa-calendar-check"></i> Check Live Schedule
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn-hero-ghost">
                        <i class="fas fa-chart-pie"></i> My Dashboard
                    </a>
                @else
                    <a href="#availability" class="btn-hero-primary">
                        <i class="fas fa-calendar-check"></i> Book Your Available Slot Now
                    </a>
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="btn-hero-ghost">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </a>
                    @endif
                    <a href="#locations" class="btn-hero-ghost">
                        <i class="fas fa-map-marked-alt"></i> View Locations
                    </a>
                @endauth
            </div>

            <!-- Quick Location Directions Group -->
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); padding: 1rem; border-radius: 10px; display: inline-block; max-width: 100%; backdrop-filter: blur(8px); margin-top: 1rem;">
                <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--green); letter-spacing: 0.05em; display: block; margin-bottom: 0.6rem;">Get Directions (Google Maps)</span>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center;">
                    @foreach ($locations as $loc)
                        @php
                            $quickMapsUrl = "https://www.google.com/maps/search/?api=1&query=";
                            if (!empty($loc->latitude) && !empty($loc->longitude)) {
                                $quickMapsUrl .= $loc->latitude . ',' . $loc->longitude;
                            } else {
                                $quickMapsUrl .= urlencode($loc->name . ', ' . $loc->address_line1 . ', ' . $loc->city);
                            }
                        @endphp
                        <a href="{{ $quickMapsUrl }}" target="_blank" class="btn-ghost" style="font-size: 0.72rem; padding: 0.4rem 0.8rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 500; background: rgba(13, 17, 23, 0.6); border-color: rgba(255,255,255,0.12);">
                            <i class="fas fa-directions" style="color: var(--green);"></i> {{ $loc->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ─── PILLS ─── --}}
    <div class="pills-section">
        <span class="pill"><i class="fas fa-check"></i> Online reservations</span>
        <span class="pill"><i class="fas fa-check"></i> GCash payments</span>
        <span class="pill"><i class="fas fa-check"></i> Equipment rental</span>
        <span class="pill"><i class="fas fa-check"></i> Digital receipt</span>
        <span class="pill"><i class="fas fa-check"></i> Easy check-in</span>
        <span class="pill"><i class="fas fa-check"></i> Cancellation support</span>
    </div>

    {{-- ─── LIVE COURT AVAILABILITY ─── --}}
    <section class="steps-section" id="availability" style="background: #080c12; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 5rem 2rem;">
        <div style="max-width: 900px; margin: 0 auto;">
            <div class="section-label">Real-Time Schedule</div>
            <h2 class="section-heading">Book your available slot now</h2>
            <p class="section-sub" style="margin-bottom: 2rem;">Select a location and date below to see live court availability. Click any open slot to reserve it instantly.</p>

            <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 2.5rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 1.25rem; border-radius: 12px; backdrop-filter: blur(8px);">
                <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 0.5rem;">
                    <label for="avail-location-select" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em;">Select Location</label>
                    <select id="avail-location-select" style="background: rgba(13, 17, 23, 0.8); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.9rem; font-family: inherit; font-weight: 500; cursor: pointer; outline: none; transition: border-color 150ms;">
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->city }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 0.5rem;">
                    <label for="avail-date-picker" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em;">Select Date</label>
                    <input type="date" id="avail-date-picker" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" style="background: rgba(13, 17, 23, 0.8); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.9rem; font-family: inherit; font-weight: 500; outline: none; transition: border-color 150ms;">
                </div>
            </div>

            <div id="availability-timeline-container" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; backdrop-filter: blur(8px);">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </section>

    {{-- ─── 3 STEPS ─── --}}
    <section class="steps-section" id="how">
        <div style="max-width:900px; margin:0 auto;">
            <div class="section-label">How it works</div>
            <h2 class="section-heading">Book a court in 3 steps.</h2>
            <p class="section-sub">No app needed. Works on any device.</p>

            <div class="steps-grid">
                <div class="step-item">
                    <div class="step-num">1</div>
                    <div class="step-title">Pick your slot</div>
                    <p class="step-desc">Choose a court, date, and time. See what's available in real time — no double bookings.</p>
                </div>
                <div class="step-item">
                    <div class="step-num">2</div>
                    <div class="step-title">Pay via GCash</div>
                    <p class="step-desc">Scan the QR or send to our GCash number. Upload your screenshot as proof — done.</p>
                </div>
                <div class="step-item">
                    <div class="step-num">3</div>
                    <div class="step-title">Show up and play</div>
                    <p class="step-desc">Staff will verify your booking. Grab your equipment rental and get on the court.</p>
                </div>
            </div>

            <div style="margin-top: 3rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center;">
                    <a href="#availability" class="btn-hero-primary" style="font-size: 0.9rem; padding: 0.75rem 1.75rem;">
                        <i class="fas fa-calendar-check"></i> Book Your Available Slot Now
                    </a>
                    <a href="#locations" class="btn-hero-ghost" style="font-size: 0.9rem; padding: 0.75rem 1.75rem;">
                        <i class="fas fa-map-marked-alt"></i> View Locations
                    </a>
                </div>

                <div style="width: 100%; max-width: 600px; margin-top: 1rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); padding: 1.25rem; border-radius: 10px;">
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--green); letter-spacing: 0.05em; display: block; margin-bottom: 0.75rem;">Get Quick Directions</span>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center;">
                        @foreach ($locations as $loc)
                            @php
                                $quickMapsUrl = "https://www.google.com/maps/search/?api=1&query=";
                                if (!empty($loc->latitude) && !empty($loc->longitude)) {
                                    $quickMapsUrl .= $loc->latitude . ',' . $loc->longitude;
                                } else {
                                    $quickMapsUrl .= urlencode($loc->name . ', ' . $loc->address_line1 . ', ' . $loc->city);
                                }
                            @endphp
                            <a href="{{ $quickMapsUrl }}" target="_blank" class="btn-ghost" style="font-size: 0.75rem; padding: 0.45rem 0.85rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                <i class="fas fa-directions"></i> {{ $loc->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── OUR LOCATIONS ─── --}}
    <section class="steps-section" id="locations" style="background: #0d1117; padding: 5rem 2rem;">
        <div style="max-width:900px; margin:0 auto;">
            <div class="section-label">Find Us</div>
            <h2 class="section-heading">Our Locations</h2>
            <p class="section-sub" style="margin-bottom: 3rem;">Visit any of our premium courts. Easily get directions or book a court directly.</p>

            <div class="locations-grid">
                @foreach ($locations as $loc)
                    <div class="location-card">
                        <div class="location-card-image" style="background-image: url('{{ $loc->featured_image_path ? asset($loc->featured_image_path) : asset('images/branding.png') }}');"></div>
                        <div class="location-card-content">
                            <h3 class="location-card-title">{{ $loc->name }}</h3>
                            <p class="location-card-address">
                                <i class="fas fa-map-marker-alt" style="color:var(--green); margin-right: 0.35rem;"></i>
                                {{ $loc->address_line1 }}{{ $loc->address_line2 ? ', ' . $loc->address_line2 : '' }}, {{ $loc->city }}
                            </p>
                            <div class="location-card-actions">
                                @php
                                    $mapsUrl = "https://www.google.com/maps/search/?api=1&query=";
                                    if (!empty($loc->latitude) && !empty($loc->longitude)) {
                                        $mapsUrl .= $loc->latitude . ',' . $loc->longitude;
                                    } else {
                                        $mapsUrl .= urlencode($loc->name . ', ' . $loc->address_line1 . ', ' . $loc->city);
                                    }
                                @endphp
                                <a href="{{ $mapsUrl }}" target="_blank" class="btn-ghost" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.6rem 1rem; font-size: 0.82rem; border-radius: 8px;">
                                    <i class="fas fa-directions"></i> Get Directions
                                </a>
                                <a href="{{ route('modules.show', ['module' => 'book-court', 'location_id' => $loc->id]) }}" class="btn-primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.6rem 1.2rem; font-size: 0.82rem; border-radius: 8px;">
                                    <i class="fas fa-calendar-plus"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── CTA ─── --}}
    <section class="cta-section">
        <div class="cta-box">
            <h2 class="hero-title">Ready to play?</h2>
            <p class="hero-sub">
                Create a free account and book your first court in under 2 minutes.
            </p>
            <div class="hero-btns">
                @auth
                    <a href="{{ route('modules.show', 'book-court') }}" class="btn-hero-primary">
                        <i class="fas fa-calendar-plus"></i> Reserve a Court
                    </a>
                @else
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-hero-primary">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                    @endif
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="btn-hero-ghost">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </section>

    {{-- ─── FOOTER ─── --}}
    <footer>
        <span class="footer-copy">© {{ date('Y') }} Pickle Ballan ni Juan. All rights reserved.</span>
        <div class="footer-links">
            @if (Route::has('login'))
                <a href="{{ route('login') }}">Sign In</a>
            @endif
            @if (Route::has('register'))
                <a href="{{ route('register') }}">Register</a>
            @endif
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const locationSelect = document.getElementById('avail-location-select');
            const datePicker = document.getElementById('avail-date-picker');
            const timelineContainer = document.getElementById('availability-timeline-container');

            if (!locationSelect || !datePicker || !timelineContainer) return;

            function fetchAvailability() {
                const locationId = locationSelect.value;
                const date = datePicker.value;

                if (!locationId || !date) return;

                timelineContainer.innerHTML = `
                    <div style="text-align:center; padding:2rem 0; color:var(--muted);">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <p class="text-xs">Loading availability...</p>
                    </div>
                `;

                fetch(`/public/availability?location_id=${locationId}&date=${date}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (!data.courts || data.courts.length === 0) {
                            timelineContainer.innerHTML = `
                                <div style="text-align:center; padding:2rem 0; color:var(--muted);">
                                    <i class="fas fa-exclamation-circle fa-2x mb-2" style="color:#f5365c;"></i>
                                    <p class="text-xs">No active courts found at this location.</p>
                                </div>
                            `;
                            return;
                        }

                        let html = '';
                        data.courts.forEach(court => {
                            html += `
                                <div style="border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem;">
                                        <h4 style="font-size:1rem; font-weight:700; color:#fff; display:flex; align-items:center; gap:0.4rem; margin:0;">
                                            <i class="fas fa-table-tennis" style="color:var(--green); font-size:0.9rem;"></i>
                                            ${court.court_name}
                                        </h4>
                                        <span style="font-size:0.78rem; color:var(--muted); font-weight:500;">
                                            Court Number: ${court.court_number}
                                        </span>
                                    </div>
                                    <div class="slots-timeline">
                            `;

                            court.slots.forEach(slot => {
                                const redirectUrl = `/modules/book-court?location_id=${locationId}&date=${date}&start_time=${slot.start}&end_time=${slot.end}&court_id=${court.court_id}`;

                                if (slot.available) {
                                    html += `
                                        <div class="slot-badge slot-hover" 
                                             onclick="window.location.href='${redirectUrl}'"
                                             title="${slot.reason}">
                                            <span class="slot-label">${slot.label}</span>
                                            <span class="btn-slot-action btn-book">
                                                <i class="fas fa-calendar-plus"></i> Book
                                            </span>
                                        </div>
                                    `;
                                } else {
                                    html += `
                                        <div class="slot-badge slot-unavailable"
                                             title="${slot.reason}">
                                            <span class="slot-label">${slot.label}</span>
                                            <span class="btn-slot-action btn-blocked">
                                                <i class="fas fa-ban"></i> ${slot.reason}
                                            </span>
                                        </div>
                                    `;
                                }
                            });

                            html += `
                                    </div>
                                </div>
                            `;
                        });

                        timelineContainer.innerHTML = html;
                    })
                    .catch(err => {
                        timelineContainer.innerHTML = `
                            <div style="text-align:center; padding:2rem 0; color:var(--muted);">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2" style="color:#f93c3c;"></i>
                                <p class="text-xs">Failed to load availability. Please try again.</p>
                            </div>
                        `;
                    });
            }

            locationSelect.addEventListener('change', fetchAvailability);
            datePicker.addEventListener('change', fetchAvailability);

            fetchAvailability();
        });
    </script>

</body>
</html>
