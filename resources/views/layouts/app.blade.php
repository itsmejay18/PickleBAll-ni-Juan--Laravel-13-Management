@php
    use App\Models\EndUserProfile;
    use App\Services\NotificationService;

    $user = Auth::user();
    $roleLabel = $user?->roles->pluck('name')->map(fn ($role) => str_replace('_', ' ', $role))->implode(', ') ?: 'End User';
    $headerContent = $header ?? 'Dashboard';
    $headerText = trim(strip_tags((string) $headerContent)) ?: 'Dashboard';
    $dashboardActive = request()->routeIs('dashboard');
    $moduleActive = fn (string $module): bool => request()->routeIs('modules.show') && request()->route('module') === $module;

    $profile = $user ? EndUserProfile::query()->where('user_id', $user->id)->first() : null;
    $displayName = $profile
        ? trim(($profile->first_name ?? '').' '.($profile->last_name ?? ''))
        : null;
    $displayName = $displayName ?: ($user?->email ?: 'Account');

    $photoUrl = ($user?->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->photo_path))
        ? \Illuminate\Support\Facades\Storage::url($user->photo_path)
        : asset('images/branding.png');

    $notificationService = app(NotificationService::class);
    $notifications = $user ? $notificationService->recent($user, 8) : collect();
    $unreadCount = $user ? $notificationService->unreadCount($user) : 0;

    $navSections = [
        [
            'label' => null,
            'items' => [
                ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => $dashboardActive, 'icon' => 'fas fa-chart-pie'],
            ],
        ],
    ];

    if ($user?->hasAnyRole(['super_admin', 'admin'])) {
        $navSections[] = [
            'label' => 'Admin',
            'items' => [
                ['label' => 'Locations', 'href' => route('modules.show', 'locations'), 'active' => $moduleActive('locations'), 'icon' => 'fas fa-map-marker-alt'],
                ['label' => 'Courts', 'href' => route('modules.show', 'courts'), 'active' => $moduleActive('courts'), 'icon' => 'fas fa-table-tennis'],
                ['label' => 'Payments', 'href' => route('modules.show', 'payments'), 'active' => $moduleActive('payments'), 'icon' => 'fas fa-money-check-alt'],
                ['label' => 'Equipment', 'href' => route('modules.show', 'equipment'), 'active' => $moduleActive('equipment'), 'icon' => 'fas fa-boxes'],
                ['label' => 'Users', 'href' => route('modules.show', 'users'), 'active' => $moduleActive('users'), 'icon' => 'fas fa-users-cog'],
                ['label' => 'Reports', 'href' => route('modules.show', 'reports'), 'active' => $moduleActive('reports'), 'icon' => 'fas fa-file-export'],
            ],
        ];
    }

    if ($user?->hasAnyRole(['location_manager', 'staff'])) {
        $navSections[] = [
            'label' => 'Staff',
            'items' => [
                ['label' => 'Walk-ins', 'href' => route('modules.show', 'walk-ins'), 'active' => $moduleActive('walk-ins'), 'icon' => 'fas fa-walking'],
                ['label' => 'Check-in', 'href' => route('modules.show', 'check-ins'), 'active' => $moduleActive('check-ins'), 'icon' => 'fas fa-clipboard-check'],
                ['label' => 'Check-out', 'href' => route('modules.show', 'check-outs'), 'active' => $moduleActive('check-outs'), 'icon' => 'fas fa-sign-out-alt'],
            ],
        ];
    }

    $customerItems = [
        ['label' => 'Book Court', 'href' => route('modules.show', 'book-court'), 'active' => $moduleActive('book-court'), 'icon' => 'fas fa-calendar-plus'],
    ];

    if (! $user?->hasAnyRole(['super_admin', 'admin', 'location_manager', 'staff'])) {
        $customerItems[] = ['label' => 'Pay GCash', 'href' => route('modules.show', 'payments'), 'active' => $moduleActive('payments'), 'icon' => 'fas fa-wallet'];
    }

    $customerItems[] = ['label' => 'Receipts', 'href' => route('modules.show', 'receipts'), 'active' => $moduleActive('receipts'), 'icon' => 'fas fa-receipt'];
    $customerItems[] = ['label' => 'Reviews', 'href' => route('modules.show', 'reviews'), 'active' => $moduleActive('reviews'), 'icon' => 'fas fa-star'];

    $navSections[] = [
        'label' => 'Customer',
        'items' => $customerItems,
    ];

@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Pickle Ballan ni Juan') }}</title>
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/branding.png') }}">
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .pbj-profile-trigger img {
                width: 36px;
                height: 36px;
                object-fit: cover;
                border-radius: 50%;
                border: 2px solid rgba(255,255,255,0.7);
                box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            }
            .pbj-profile-trigger {
                cursor: pointer;
                gap: 0.55rem;
            }
            .pbj-notification-dot {
                position: absolute;
                top: 4px;
                right: 4px;
                min-width: 16px;
                height: 16px;
                padding: 0 4px;
                font-size: 10px;
                font-weight: 700;
                line-height: 16px;
                color: #fff;
                background: #f5365c;
                border-radius: 8px;
                text-align: center;
            }
            .pbj-notification-list {
                width: min(360px, 92vw);
                max-height: 480px;
                overflow-y: auto;
            }
            .pbj-notification-item.unread {
                background: rgba(94, 114, 228, 0.08);
            }
            .pbj-empty-state {
                padding: 1.5rem 1rem;
                color: #67748e;
                text-align: center;
                font-size: 0.85rem;
            }
        </style>
    </head>
    <body class="g-sidenav-show bg-gray-100">
        <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3" id="sidenav-main">
            <div class="sidenav-header">
                <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-xl-none" aria-hidden="true" id="iconSidenav"></i>
                <a class="navbar-brand m-0 d-flex align-items-center" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/branding.png') }}" class="navbar-brand-img pbj-brand-mark-sm" alt="Pickle Ballan ni Juan">
                    <span class="ms-1 font-weight-bold">Pickle Ballan ni Juan</span>
                </a>
            </div>

            <hr class="horizontal dark mt-0">

            <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
                <ul class="navbar-nav">
                    @foreach ($navSections as $section)
                        @if ($section['label'])
                            <li class="nav-item mt-3">
                                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">{{ $section['label'] }}</h6>
                            </li>
                        @endif

                        @foreach ($section['items'] as $item)
                            <li class="nav-item">
                                <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['href'] }}">
                                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                                        <i class="{{ $item['icon'] }} text-dark text-gradient text-sm opacity-10" aria-hidden="true"></i>
                                    </div>
                                    <span class="nav-link-text ms-1">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </div>

        </aside>

        <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
            <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
                <div class="container-fluid py-1 px-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                            <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="{{ route('dashboard') }}">Pages</a></li>
                            <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ $headerText }}</li>
                        </ol>
                        <h6 class="font-weight-bolder mb-0">{{ $headerText }}</h6>
                    </nav>
                    <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
                        <ul class="navbar-nav justify-content-end ms-auto">
                            <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                                <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                                    <div class="sidenav-toggler-inner">
                                        <i class="sidenav-toggler-line"></i>
                                        <i class="sidenav-toggler-line"></i>
                                        <i class="sidenav-toggler-line"></i>
                                    </div>
                                </a>
                            </li>

                            {{-- NOTIFICATIONS bell with real per-user data --}}
                            <li class="nav-item dropdown pe-2 d-flex align-items-center">
                                <a href="javascript:;" class="nav-link text-body p-0 position-relative pbj-bell"
                                   id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                                   data-pbj-feed="{{ route('notifications.index') }}"
                                   title="Notifications">
                                    <i class="fa fa-bell cursor-pointer fa-lg"></i>
                                    <span class="pbj-notification-dot pbj-bell-count" data-count="{{ $unreadCount }}" style="{{ $unreadCount > 0 ? '' : 'display:none;' }}">
                                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end pbj-notification-list px-2 py-2"
                                    aria-labelledby="notificationsDropdown">
                                    <li class="d-flex align-items-center justify-content-between px-2 py-1">
                                        <span class="text-sm font-weight-bold">Notifications</span>
                                        @if ($notifications->isNotEmpty() && $unreadCount > 0)
                                            <form method="POST" action="{{ route('notifications.read-all') }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-link btn-sm p-0 text-xs text-info">Mark all read</button>
                                            </form>
                                        @endif
                                    </li>
                                    <li><hr class="horizontal dark my-1"></li>

                                    @forelse ($notifications as $notification)
                                        @php
                                            $targetUrl = $notification->reservation_id
                                                ? route('receipts.show', $notification->reservation_id)
                                                : null;
                                        @endphp
                                        <li class="pbj-notification-item border-radius-md {{ $notification->read_at === null ? 'unread' : '' }}">
                                            <form method="POST"
                                                action="{{ route('notifications.read', $notification->id) }}"
                                                class="m-0 pbj-notification-form"
                                                @if ($targetUrl) data-pbj-target="{{ $targetUrl }}" @endif>
                                                @csrf
                                                <button type="submit" class="dropdown-item border-radius-md text-start w-100 py-2">
                                                    <div class="d-flex">
                                                        <div class="avatar avatar-sm bg-gradient-{{ $notification->channel === 'admin' ? 'dark' : ($notification->channel === 'staff' ? 'info' : 'primary') }} me-3 my-auto flex-shrink-0">
                                                            <i class="fas fa-{{ $notification->channel === 'admin' ? 'user-shield' : ($notification->channel === 'staff' ? 'user-cog' : 'bell') }} text-white text-sm"></i>
                                                        </div>
                                                        <div class="d-flex flex-column justify-content-center">
                                                            <h6 class="text-sm font-weight-bold mb-1 text-wrap">{{ $notification->subject }}</h6>
                                                            @if ($notification->message)
                                                                <p class="text-xs text-secondary mb-1 text-wrap">{{ \Illuminate\Support\Str::limit($notification->message, 100) }}</p>
                                                            @endif
                                                            <p class="text-xs text-secondary mb-0">
                                                                <i class="fa fa-clock me-1"></i>{{ $notification->created_at?->diffForHumans() }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </button>
                                            </form>
                                        </li>
                                    @empty
                                        <li class="pbj-empty-state">
                                            <i class="fas fa-bell-slash mb-2 d-block text-secondary"></i>
                                            You're all caught up.
                                        </li>
                                    @endforelse

                                    <li><hr class="horizontal dark my-1"></li>
                                    <li>
                                        <a class="dropdown-item border-radius-md text-center text-xs text-info py-2"
                                           href="{{ route('notifications.show') }}">
                                            View all notifications
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            {{-- PROFILE dropdown with logout --}}
                            <li class="nav-item dropdown pe-2 d-flex align-items-center">
                                <a href="javascript:;" class="nav-link text-body font-weight-bold px-2 d-flex align-items-center pbj-profile-trigger"
                                   id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="{{ $photoUrl }}" alt="{{ $displayName }}">
                                    <div class="d-none d-sm-block text-start">
                                        <span class="text-sm font-weight-bolder d-block">{{ $displayName }}</span>
                                        <span class="text-xs text-secondary d-block text-capitalize">{{ $roleLabel }}</span>
                                    </div>
                                    <i class="fas fa-chevron-down text-xs ms-1 text-secondary"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end px-2 py-2" aria-labelledby="profileDropdown" style="min-width: 220px;">
                                    <li class="px-2 py-2 border-bottom">
                                        <p class="text-sm font-weight-bold mb-0">{{ $displayName }}</p>
                                        <p class="text-xs text-secondary mb-0">{{ $user?->email }}</p>
                                    </li>
                                    <li>
                                        <a class="dropdown-item border-radius-md py-2" href="{{ route('profile.edit') }}">
                                            <i class="fas fa-user me-2 text-secondary"></i>My Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item border-radius-md py-2" href="{{ route('dashboard') }}">
                                            <i class="fas fa-chart-pie me-2 text-secondary"></i>Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="horizontal dark my-1"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="dropdown-item border-radius-md py-2 text-danger w-100 text-start">
                                                <i class="fas fa-power-off me-2"></i>Log out
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid py-4">
                @if (session('status'))
                    <div class="alert alert-success text-white text-sm alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close text-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger text-white text-sm alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close text-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-warning text-white text-sm alert-dismissible fade show" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close text-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>

        @stack('modals')

        <script>
            (function () {
                document.addEventListener('submit', function (event) {
                    const form = event.target.closest('.pbj-notification-form');
                    if (!form) return;
                    const target = form.dataset.pbjTarget;
                    if (!target) return;
                    event.preventDefault();
                    const data = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        body: data,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    }).finally(() => {
                        window.location.href = target;
                    });
                });

                const bell = document.querySelector('.pbj-bell');
                if (!bell) return;
                const feed = bell.dataset.pbjFeed;
                if (!feed) return;

                async function refreshBadge() {
                    try {
                        const res = await fetch(feed, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        const dot = bell.querySelector('.pbj-bell-count');
                        if (!dot) return;
                        const unread = parseInt(data.unread || 0, 10);
                        dot.textContent = unread > 99 ? '99+' : String(unread);
                        dot.style.display = unread > 0 ? '' : 'none';
                    } catch (err) { /* offline / ignore */ }
                }

                setInterval(refreshBadge, 60000);
            })();
        </script>
    </body>
</html>
