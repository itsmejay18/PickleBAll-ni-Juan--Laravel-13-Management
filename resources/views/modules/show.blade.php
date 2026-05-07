<x-app-layout>
    <x-slot name="header">
        {{ $page['title'] }}
    </x-slot>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <p class="text-xs text-uppercase font-weight-bolder text-info mb-2">{{ $page['eyebrow'] }}</p>
                            <h3 class="font-weight-bolder mb-2">{{ $page['title'] }}</h3>
                            <p class="text-secondary mb-0">{{ $page['description'] }}</p>
                        </div>
                        <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                            <i class="fas {{ $page['icon'] }} text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body p-4">
                    <p class="text-sm mb-1 text-secondary">Owner</p>
                    <h6>{{ $page['owner'] }}</h6>
                    <hr class="horizontal dark">
                    <p class="text-sm mb-1 text-secondary">Objectives</p>
                    <h6>{{ $page['objectives'] }}</h6>
                    <hr class="horizontal dark">
                    <p class="text-sm mb-1 text-secondary">Status</p>
                    <span class="badge badge-sm bg-gradient-secondary">{{ $page['status'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($page['cards'] as $card)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="icon icon-shape bg-gradient-dark shadow text-center border-radius-md mb-3">
                            <i class="fas {{ $card['icon'] }} text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                        <h6>{{ $card['title'] }}</h6>
                        <p class="text-sm text-secondary mb-0">{{ $card['text'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Objective Map</h6>
                    <p class="text-sm mb-0">This screen is a clickable module shell while the full CRUD workflow is still being built.</p>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Feature</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Objective</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($page['rows'] as $row)
                                    <tr>
                                        <td><div class="d-flex px-3 py-1"><h6 class="mb-0 text-sm">{{ $row['feature'] }}</h6></div></td>
                                        <td><p class="text-sm font-weight-bold mb-0">{{ $row['objective'] }}</p></td>
                                        <td><p class="text-sm mb-0">{{ $row['note'] }}</p></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
