<x-app-layout>
    <x-slot name="header">Global Leaderboard</x-slot>

    <div class="container-fluid py-4">
        {{-- ===== Top 3 Podium ===== --}}
        <div class="row mb-4 justify-content-center align-items-end">
            @php
                $podium = $players->take(3);
                $first = $podium->get(0);
                $second = $podium->get(1);
                $third = $podium->get(2);
            @endphp

            {{-- 2nd Place --}}
            @if ($second)
                <div class="col-md-3 col-sm-4 order-2 order-sm-1 mb-3">
                    <div class="card border border-radius-lg text-center bg-gradient-dark p-3 text-white" style="border-color: #cbd5e1 !important; transform: scale(0.95); opacity: 0.95;">
                        <span class="fs-1">🥈</span>
                        <h6 class="text-white mt-2 mb-1">{{ $second->endUserProfile ? $second->endUserProfile->first_name . ' ' . $second->endUserProfile->last_name : $second->email }}</h6>
                        <span class="badge bg-gradient-info text-xxs font-weight-bold mb-2">Court Champion</span>
                        <div class="text-sm font-weight-bold text-success">{{ $second->rating }} pts</div>
                        <p class="text-xs text-secondary mb-0 mt-2">{{ $second->wins }}W - {{ $second->losses }}L ({{ $second->win_rate }}%)</p>
                    </div>
                </div>
            @endif

            {{-- 1st Place --}}
            @if ($first)
                <div class="col-md-4 col-sm-4 order-1 order-sm-2 mb-3">
                    <div class="card border border-radius-lg text-center bg-gradient-dark p-4 text-white shadow-lg" style="border-color: #fbbf24 !important; transform: scale(1.05); position: relative; z-index: 10;">
                        <span class="fs-1">👑</span>
                        <h5 class="text-white mt-2 mb-1">{{ $first->endUserProfile ? $first->endUserProfile->first_name . ' ' . $first->endUserProfile->last_name : $first->email }}</h5>
                        <span class="badge bg-gradient-warning text-xs font-weight-bold mb-2">King of the Court</span>
                        <div class="text-lg font-weight-bold text-success">{{ $first->rating }} pts</div>
                        <p class="text-xs text-secondary mb-0 mt-2">{{ $first->wins }}W - {{ $first->losses }}L ({{ $first->win_rate }}%)</p>
                    </div>
                </div>
            @endif

            {{-- 3rd Place --}}
            @if ($third)
                <div class="col-md-3 col-sm-4 order-3 order-sm-3 mb-3">
                    <div class="card border border-radius-lg text-center bg-gradient-dark p-3 text-white" style="border-color: #b45309 !important; transform: scale(0.95); opacity: 0.95;">
                        <span class="fs-1">🥉</span>
                        <h6 class="text-white mt-2 mb-1">{{ $third->endUserProfile ? $third->endUserProfile->first_name . ' ' . $third->endUserProfile->last_name : $third->email }}</h6>
                        <span class="badge bg-gradient-light text-xxs font-weight-bold text-dark mb-2">Court Contender</span>
                        <div class="text-sm font-weight-bold text-success">{{ $third->rating }} pts</div>
                        <p class="text-xs text-secondary mb-0 mt-2">{{ $third->wins }}W - {{ $third->losses }}L ({{ $third->win_rate }}%)</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===== Main Leaderboard List ===== --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h6 class="mb-0"><i class="fas fa-trophy text-warning me-2"></i>Global Player Rankings</h6>
                            <p class="text-xs text-secondary mb-0">List of all registered players ordered by court rating.</p>
                        </div>
                        <a href="{{ route('open-play.tv') }}" target="_blank" class="btn btn-sm bg-gradient-dark mb-0"><i class="fas fa-tv me-1"></i>Open TV Dashboard</a>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0" id="globalLeaderboardTable">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder ps-3" style="width: 80px;">Rank</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Player</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder text-center">Division & Badge</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder text-center">Rating</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder text-center">Matches Played</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder text-center">Record (W-L)</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder text-center">Win Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($players as $index => $player)
                                        @php
                                            // Calculate absolute rank across pages
                                            $rank = ($players->currentPage() - 1) * $players->perPage() + $index + 1;
                                        @endphp
                                        <tr>
                                            <td class="ps-3">
                                                @if ($rank === 1)
                                                    <span class="badge bg-gradient-warning text-xs font-weight-bold">#1</span>
                                                @elseif ($rank === 2)
                                                    <span class="badge bg-gradient-secondary text-xs font-weight-bold">#2</span>
                                                @elseif ($rank === 3)
                                                    <span class="badge bg-gradient-light text-dark text-xs font-weight-bold">#3</span>
                                                @else
                                                    <span class="text-sm font-weight-bold text-secondary">#{{ $rank }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex px-2 py-1 align-items-center">
                                                    <div class="me-3 fs-2">
                                                        {{ $player->badge_emoji }}
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm">{{ $player->endUserProfile ? $player->endUserProfile->first_name . ' ' . $player->endUserProfile->last_name : $player->email }}</h6>
                                                        <span class="text-xs text-secondary">{{ $player->email }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="badge bg-gradient-dark text-xxs font-weight-bold">
                                                    {{ $player->badge_name }}
                                                </span>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-sm font-weight-bold text-success">{{ $player->rating }}</span>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-sm font-weight-bold text-dark">{{ $player->matches_played }}</span>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-sm font-weight-bold">{{ $player->wins }} - {{ $player->losses }}</span>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-sm font-weight-bold text-info">{{ $player->win_rate }}%</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-secondary py-4">No player statistics available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination links --}}
                        <div class="d-flex justify-content-center py-3">
                            {{ $players->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
