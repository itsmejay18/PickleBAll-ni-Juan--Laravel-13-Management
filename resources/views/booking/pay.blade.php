<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete Payment — {{ $reservation->reservation_code }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('soft-ui-dashboard-main/assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('soft-ui-dashboard-main/assets/css/soft-ui-dashboard.css') }}" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        /* ─── TOP BAR ─── */
        .pay-topbar {
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.07);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .pay-topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: #17202a;
            text-decoration: none;
        }

        .pay-topbar-brand img {
            width: 28px; height: 28px;
            border-radius: 6px; object-fit: cover;
        }

        .pay-step-trail {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: #8392ab;
        }

        .pay-step-trail .active { color: #17202a; font-weight: 600; }
        .pay-step-trail .dot { width: 4px; height: 4px; border-radius: 50%; background: #cbd5e1; }

        /* ─── MAIN LAYOUT ─── */
        .pay-wrap {
            flex: 1;
            max-width: 900px;
            width: 100%;
            margin: 2.5rem auto;
            padding: 0 1.25rem;
        }

        /* ─── BOOKING SUMMARY CARD ─── */
        .summary-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.07);
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .summary-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.75rem;
            background: #fff8e1;
            border: 1px solid #ffd54f;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #b45309;
        }

        .summary-code {
            font-size: 0.75rem;
            color: #8392ab;
            margin-bottom: 0.25rem;
        }

        .summary-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #17202a;
            margin-bottom: 0.15rem;
        }

        .summary-meta {
            font-size: 0.82rem;
            color: #67748e;
        }

        .summary-amount {
            text-align: right;
        }

        .summary-amount-label {
            font-size: 0.75rem;
            color: #8392ab;
            margin-bottom: 0.2rem;
        }

        .summary-amount-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #17202a;
            line-height: 1;
        }

        /* ─── CHOICE BUTTONS ─── */
        .choice-section {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.07);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .choice-header {
            padding: 1.25rem 1.5rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }

        .choice-header h6 {
            font-size: 0.9rem;
            font-weight: 700;
            color: #17202a;
            margin: 0 0 0.2rem;
        }

        .choice-header p {
            font-size: 0.8rem;
            color: #8392ab;
            margin: 0;
        }

        .choice-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding: 1.25rem;
        }

        .choice-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 1.5rem 1rem;
            border: 2px solid #e2e8f0;
            background: #fff;
            border-radius: 12px;
            cursor: pointer;
            transition: all 200ms ease;
            text-decoration: none;
            color: inherit;
        }

        .choice-btn:hover {
            border-color: #cbd5e1;
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }

        .choice-btn.active-choice {
            border-color: #18a37f;
            background: #f0fdf4;
            box-shadow: 0 4px 15px rgba(24, 163, 127, 0.12);
        }

        .choice-btn#btnChooseLater.active-choice {
            border-color: #64748b;
            background: #f8fafc;
            box-shadow: 0 4px 15px rgba(100, 116, 139, 0.12);
        }

        .choice-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
            color: #fff;
        }

        .choice-icon.green  { background: linear-gradient(135deg, #18a37f, #0d9488); }
        .choice-icon.gray   { background: linear-gradient(135deg, #8392ab, #67748e); }

        .choice-label {
            font-size: 0.9rem;
            font-weight: 700;
            color: #17202a;
        }

        .choice-sub {
            font-size: 0.75rem;
            color: #8392ab;
            text-align: center;
        }

        /* ─── GCASH PANEL ─── */
        .gcash-panel {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.07);
            overflow: hidden;
        }

        .gcash-panel-header {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .gcash-panel-header h6 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 0.1rem;
        }

        .gcash-panel-header p {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.78);
            margin: 0;
        }

        .gcash-body {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* QR + number */
        .qr-block { text-align: center; }

        /* Glowing QR Container */
        .qr-container {
            width: 220px;
            height: 220px;
            margin: 0 auto 1rem;
            position: relative;
            background: #fff;
            border-radius: 16px;
            border: 4px solid #005bba;
            box-shadow: 0 8px 30px rgba(0, 91, 186, 0.15), 0 0 15px rgba(0, 91, 186, 0.2);
            padding: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .qr-container:hover {
            transform: scale(1.03);
            box-shadow: 0 12px 40px rgba(0, 91, 186, 0.25), 0 0 25px rgba(0, 91, 186, 0.35);
        }

        .qr-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            margin: 0;
            border: none;
            padding: 0;
        }

        /* Scanner Laser Line Animation */
        .scan-laser {
            position: absolute;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, rgba(0,91,186,0) 0%, #00d2ff 50%, rgba(0,91,186,0) 100%);
            box-shadow: 0 0 8px #00d2ff, 0 0 12px #005bba;
            z-index: 10;
            animation: scanMove 2s infinite ease-in-out;
        }

        @keyframes scanMove {
            0% { top: 5%; }
            50% { top: 90%; }
            100% { top: 5%; }
        }

        .qr-placeholder {
            width: 200px;
            height: 200px;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 0 auto 0.75rem;
            color: #94a3b8;
            font-size: 0.78rem;
            text-align: center;
            padding: 1rem;
        }

        /* Copy Button styling */
        .qr-number-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #f1f5f9;
            padding: 0.4rem 0.8rem;
            border-radius: 100px;
            width: fit-content;
            margin: 0.5rem auto 0.75rem;
            border: 1px solid #cbd5e1;
            position: relative;
        }

        .qr-number {
            font-size: 1.15rem;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: 0.05em;
        }

        .btn-copy {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #475569;
            transition: all 150ms ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .btn-copy:hover {
            color: #005bba;
            border-color: #005bba;
            background: #f0f7ff;
            transform: scale(1.05);
        }

        .btn-copy:active {
            transform: scale(0.95);
        }

        /* Tooltip style */
        .copy-tooltip {
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%) translateY(5px);
            background: #1e293b;
            color: #fff;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .copy-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1e293b;
        }

        .copy-tooltip.show-tip {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .qr-label {
            font-size: 0.75rem;
            color: #8392ab;
        }

        .amount-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 1rem;
            background: #dcfce7;
            border: 1px solid #86efac;
            border-radius: 100px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #166534;
            margin-top: 0.5rem;
        }

        /* Upload form */
        .upload-block {}

        .upload-step {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }

        .upload-step-num {
            flex-shrink: 0;
            width: 26px; height: 26px;
            border-radius: 50%;
            background: #17202a;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            margin-top: 0.1rem;
        }

        .upload-step-text {
            font-size: 0.85rem;
            color: #67748e;
            line-height: 1.5;
        }

        .upload-step-text strong { color: #17202a; }

        .upload-divider {
            height: 1px;
            background: rgba(0,0,0,0.06);
            margin: 1.25rem 0;
        }

        .form-label-xs {
            font-size: 0.75rem;
            font-weight: 600;
            color: #67748e;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.4rem;
            display: block;
        }

        .pay-input {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            font-family: inherit;
            color: #17202a;
            background: #fff;
            transition: border-color 160ms, box-shadow 160ms;
            outline: none;
        }

        .pay-input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.12);
        }

        /* File drop zone */
        .drop-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 160ms, background 160ms;
            position: relative;
        }

        .drop-zone:hover, .drop-zone.dragover {
            border-color: #0ea5e9;
            background: #f0f9ff;
        }

        .drop-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .drop-zone-icon {
            font-size: 2rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .drop-zone-text {
            font-size: 0.85rem;
            color: #67748e;
            margin-bottom: 0.25rem;
        }

        .drop-zone-sub {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        #previewImg {
            max-width: 100%;
            max-height: 160px;
            border-radius: 8px;
            margin-top: 0.75rem;
            display: none;
            object-fit: contain;
        }

        /* Submit button */
        .btn-pay-submit {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #18a37f, #0d9488);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: opacity 160ms, transform 160ms;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.25rem;
        }

        .btn-pay-submit:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Pay later section */
        .later-panel {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.07);
            padding: 2rem;
            text-align: center;
        }

        .later-panel h6 { font-size: 1rem; font-weight: 700; color: #17202a; margin-bottom: 0.5rem; }
        .later-panel p  { font-size: 0.875rem; color: #67748e; margin-bottom: 1.5rem; }

        .btn-later-dash {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.75rem;
            background: #17202a;
            color: #fff;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            transition: opacity 160ms;
        }

        .btn-later-dash:hover { opacity: 0.85; color: #fff; }

        @media (max-width: 640px) {
            .gcash-body { grid-template-columns: 1fr; }
            .qr-block { display: block; margin-bottom: 1.5rem; }
            .choice-buttons { grid-template-columns: 1fr; gap: 0.75rem; padding: 1rem; }
            .summary-amount { text-align: left; }
        }

        /* QR Expand Button */
        .qr-expand-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #005bba;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            font-size: 0.85rem;
            z-index: 20;
            transition: all 0.2s ease;
        }

        .qr-container:hover .qr-expand-btn {
            transform: scale(1.1);
            background: #005bba;
            color: #fff;
        }

        /* ─── LIGHTBOX MODAL OVERLAY ─── */
        .qr-lightbox {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .qr-lightbox.show {
            opacity: 1;
            pointer-events: auto;
        }
        .qr-lightbox-content {
            background: #fff;
            padding: 1.5rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 90%;
            width: 440px;
            position: relative;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-align: center;
        }
        .qr-lightbox.show .qr-lightbox-content {
            transform: scale(1);
        }
        .qr-lightbox-close {
            position: absolute;
            top: -12px;
            right: -12px;
            width: 36px;
            height: 36px;
            background: #ef4444;
            color: #fff;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
            font-size: 1.2rem;
            font-weight: 700;
            transition: all 0.2s ease;
            z-index: 1100;
            line-height: 1;
        }
        .qr-lightbox-close:hover {
            transform: scale(1.1) rotate(90deg);
            background: #dc2626;
        }
        .qr-lightbox-close:active {
            transform: scale(0.95);
        }
        .qr-lightbox img {
            width: 100%;
            height: auto;
            max-height: 480px;
            object-fit: contain;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .qr-lightbox-desc {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }
        .qr-lightbox-desc strong {
            color: #1e293b;
        }
    </style>
</head>
<body>

    {{-- TOP BAR --}}
    <div class="pay-topbar">
        <a href="{{ route('dashboard') }}" class="pay-topbar-brand">
            <img src="{{ asset('images/branding.png') }}" alt="PBJ">
            Pickle Ballan ni Juan
        </a>
        <div class="pay-step-trail">
            <span>Book Court</span>
            <div class="dot"></div>
            <span class="active">Payment</span>
            <div class="dot"></div>
            <span>Confirmed</span>
        </div>
    </div>

    <div class="pay-wrap">

        @if (session('status'))
            <div style="background:#dcfce7; border:1px solid #86efac; border-radius:10px; padding:0.85rem 1.25rem; margin-bottom:1.25rem; font-size:0.85rem; color:#166534; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-check-circle"></i> {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="background:#fee2e2; border:1px solid #fca5a5; border-radius:10px; padding:0.85rem 1.25rem; margin-bottom:1.25rem; font-size:0.85rem; color:#991b1b;">
                @foreach ($errors->all() as $error)
                    <div><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- BOOKING SUMMARY --}}
        <div class="summary-card">
            <div>
                <div class="summary-code">Booking #{{ $reservation->reservation_code }}</div>
                <div class="summary-title">
                    {{ $reservation->location_name }} — Court {{ $reservation->court_number }}
                    @if($reservation->court_name) ({{ $reservation->court_name }}) @endif
                </div>
                <div class="summary-meta">
                    <i class="fas fa-calendar-alt me-1"></i>
                    {{ \Carbon\Carbon::parse($reservation->reservation_date)->format('F j, Y') }}
                    &nbsp;·&nbsp;
                    <i class="fas fa-clock me-1"></i>
                    {{ \Carbon\Carbon::parse($reservation->start_time)->format('g:i A') }} –
                    {{ \Carbon\Carbon::parse($reservation->end_time)->format('g:i A') }}
                </div>
            </div>
            <div class="summary-amount">
                <div class="summary-amount-label">Total to Pay</div>
                <div class="summary-amount-value">PHP {{ number_format($reservation->grand_total, 2) }}</div>
                <span class="summary-badge mt-1">
                    <i class="fas fa-clock" style="font-size:0.65rem;"></i>
                    Pending Payment
                </span>
            </div>
        </div>

        {{-- CHOICE --}}
        <div class="choice-section">
            <div class="choice-header">
                <h6>How do you want to pay?</h6>
                <p>Choose to pay now or come back later via the Pay GCash menu.</p>
            </div>
            <div class="choice-buttons">
                <button type="button" class="choice-btn active-choice" id="btnChooseNow" onclick="showPanel('now')">
                    <div class="choice-icon green"><i class="fas fa-mobile-alt"></i></div>
                    <div class="choice-label">Pay Now via GCash</div>
                    <div class="choice-sub">Scan QR &amp; upload screenshot</div>
                </button>
                <button type="button" class="choice-btn" id="btnChooseLater" onclick="showPanel('later')">
                    <div class="choice-icon gray"><i class="fas fa-clock"></i></div>
                    <div class="choice-label">Pay Later</div>
                    <div class="choice-sub">We'll hold your booking for 2 hours</div>
                </button>
            </div>
        </div>

        {{-- PAY NOW PANEL --}}
        <div id="panelNow" class="gcash-panel">
            <div class="gcash-panel-header">
                <div style="width:38px; height:38px; border-radius:10px; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas fa-mobile-alt" style="color:#fff; font-size:1.1rem;"></i>
                </div>
                <div>
                    <h6>Pay via GCash</h6>
                    <p>Scan the QR or send to the number, then upload your screenshot below.</p>
                </div>
            </div>

            <div class="gcash-body">
                {{-- QR + Number --}}
                <div class="qr-block">
                    @if (!empty($ownerGcashQr))
                        <div class="qr-container" onclick="openQrLightbox()" title="Click to enlarge QR Code">
                            <div class="scan-laser"></div>
                            <div class="qr-expand-btn">
                                <i class="fas fa-expand-alt"></i>
                            </div>
                            <img src="{{ asset($ownerGcashQr) }}" alt="GCash QR Code">
                        </div>
                    @else
                        <div class="qr-placeholder">
                            <i class="fas fa-qrcode" style="font-size:2.5rem;"></i>
                            QR code not set.<br>Use number below.
                        </div>
                    @endif
                    
                    <div class="qr-number-wrapper">
                        <span id="gcashNumberText" class="qr-number">{{ $ownerGcashNumber }}</span>
                        <button type="button" class="btn-copy" onclick="copyGcashNumber()" title="Copy GCash Number">
                            <i class="far fa-copy"></i>
                        </button>
                        <span id="copyTooltip" class="copy-tooltip">Copied!</span>
                    </div>
                    <div class="qr-label">GCash Number</div>
                    
                    <div class="amount-pill">
                        <i class="fas fa-peso-sign" style="font-size:0.75rem;"></i>
                        Send PHP {{ number_format($reservation->grand_total, 2) }}
                    </div>
                </div>

                {{-- Upload Form --}}
                <div class="upload-block">
                    <div class="upload-step">
                        <div class="upload-step-num">1</div>
                        <div class="upload-step-text">Open your GCash app and send exactly <strong>PHP {{ number_format($reservation->grand_total, 2) }}</strong> to <strong>{{ $ownerGcashNumber }}</strong></div>
                    </div>
                    <div class="upload-step">
                        <div class="upload-step-num">2</div>
                        <div class="upload-step-text">Screenshot the <strong>GCash confirmation screen</strong> showing the reference number and amount</div>
                    </div>
                    <div class="upload-step">
                        <div class="upload-step-num">3</div>
                        <div class="upload-step-text">Fill in the details below and upload your screenshot</div>
                    </div>

                    <div class="upload-divider"></div>

                    <form method="POST"
                          action="{{ route('payments.proof.store') }}"
                          enctype="multipart/form-data"
                          id="gcashUploadForm">
                        @csrf
                        <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label-xs">GCash Reference Number <span style="color:#e53e3e;">*</span></label>
                                <input type="text"
                                       name="gcash_reference_number"
                                       class="pay-input"
                                       placeholder="e.g. 1234567890"
                                       value="{{ old('gcash_reference_number') }}"
                                       required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label-xs">Your GCash Number <span style="color:#8392ab; font-weight:400; text-transform:none;">(optional)</span></label>
                                <input type="text"
                                       name="gcash_sender_number"
                                       class="pay-input"
                                       placeholder="09XXXXXXXXX"
                                       value="{{ old('gcash_sender_number', auth()->user()->mobile_number) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label-xs">GCash Screenshot <span style="color:#e53e3e;">*</span></label>
                                <div class="drop-zone" id="dropZone">
                                    <input type="file"
                                           name="gcash_screenshot"
                                           id="screenshotInput"
                                           accept="image/png,image/jpeg,image/webp"
                                           required>
                                    <div id="dropZoneContent">
                                        <div class="drop-zone-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                        <div class="drop-zone-text">Click or drag your screenshot here</div>
                                        <div class="drop-zone-sub">JPG, PNG, or WEBP · Max 5MB</div>
                                    </div>
                                    <img id="previewImg" src="" alt="Preview">
                                </div>
                            </div>
                        </div>

                        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:0.75rem 1rem; font-size:0.8rem; color:#92400e; margin-top:1rem; display:flex; gap:0.5rem; align-items:flex-start;">
                            <i class="fas fa-exclamation-triangle" style="margin-top:0.1rem; flex-shrink:0;"></i>
                            Make sure you've already <strong>sent the payment</strong> before submitting. Your booking will move to "Under Review" and admin will confirm within the day.
                        </div>

                        <button type="submit" class="btn-pay-submit">
                            <i class="fas fa-check-circle"></i>
                            Submit Payment Proof
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- PAY LATER PANEL --}}
        <div id="panelLater" class="later-panel" style="display:none;">
            <div style="width:60px; height:60px; border-radius:16px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                <i class="fas fa-clock" style="font-size:1.5rem; color:#67748e;"></i>
            </div>
            <h6>Your booking is saved!</h6>
            <p>
                Booking <strong>{{ $reservation->reservation_code }}</strong> is reserved for you.<br>
                You have <strong>2 hours</strong> to complete payment before it expires.<br>
                Go to <strong>Pay GCash</strong> in the menu when you're ready.
            </p>
            <a href="{{ route('dashboard') }}" class="btn-later-dash">
                <i class="fas fa-chart-pie"></i> Go to Dashboard
            </a>
        </div>

    </div>{{-- end pay-wrap --}}

    <script src="{{ asset('soft-ui-dashboard-main/assets/js/core/bootstrap.bundle.min.js') }}"></script>
    <script>
    function copyGcashNumber() {
        const numText = document.getElementById('gcashNumberText').innerText;
        navigator.clipboard.writeText(numText).then(() => {
            const tip = document.getElementById('copyTooltip');
            tip.classList.add('show-tip');
            setTimeout(() => tip.classList.remove('show-tip'), 2000);
        }).catch(err => {
            // Fallback for non-HTTPS or unsupported browsers
            const textArea = document.createElement("textarea");
            textArea.value = numText;
            textArea.style.position = "fixed"; 
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                const tip = document.getElementById('copyTooltip');
                tip.classList.add('show-tip');
                setTimeout(() => tip.classList.remove('show-tip'), 2000);
            } catch (err) {
                console.error('Fallback copy failed', err);
            }
            document.body.removeChild(textArea);
        });
    }

    function showPanel(choice) {
        const panelNow   = document.getElementById('panelNow');
        const panelLater = document.getElementById('panelLater');
        const btnNow     = document.getElementById('btnChooseNow');
        const btnLater   = document.getElementById('btnChooseLater');

        if (choice === 'now') {
            panelNow.style.display   = '';
            panelLater.style.display = 'none';
            btnNow.classList.add('active-choice');
            btnLater.classList.remove('active-choice');
        } else {
            panelNow.style.display   = 'none';
            panelLater.style.display = '';
            btnLater.classList.add('active-choice');
            btnNow.classList.remove('active-choice');
        }
    }

    // File preview
    const screenshotInput = document.getElementById('screenshotInput');
    const previewImg      = document.getElementById('previewImg');
    const dropZoneContent = document.getElementById('dropZoneContent');
    const dropZone        = document.getElementById('dropZone');

    if (screenshotInput) {
        screenshotInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                dropZoneContent.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    if (dropZone) {
        dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop',      e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && screenshotInput) {
                const dt = new DataTransfer();
                dt.items.add(file);
                screenshotInput.files = dt.files;
                screenshotInput.dispatchEvent(new Event('change'));
            }
        });
    }

    function openQrLightbox() {
        const lightbox = document.getElementById('qrLightbox');
        if (lightbox) {
            lightbox.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeQrLightbox() {
        const lightbox = document.getElementById('qrLightbox');
        if (lightbox) {
            lightbox.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQrLightbox();
        }
    });
    </script>

    <!-- Lightbox Modal for QR Code -->
    <div id="qrLightbox" class="qr-lightbox" onclick="closeQrLightbox()">
        <div class="qr-lightbox-content" onclick="event.stopPropagation()">
            <button type="button" class="qr-lightbox-close" onclick="closeQrLightbox()">&times;</button>
            @if (!empty($ownerGcashQr))
                <img src="{{ asset($ownerGcashQr) }}" alt="GCash QR Code Full">
            @endif
            <div class="qr-lightbox-desc">
                Scan QR code dynamically to pay <strong>PHP {{ number_format($reservation->grand_total, 2) }}</strong>
            </div>
        </div>
    </div>
</body>
</html>
