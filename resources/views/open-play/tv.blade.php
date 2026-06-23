<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open Play Live TV Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --neon-green: #22c55e;
            --neon-blue: #0ea5e9;
            --neon-yellow: #eab308;
            --text-main: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: #334155;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
        }

        /* Header Style */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        header h1 span {
            color: var(--neon-green);
        }

        .header-meta {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .meta-pill {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1.2rem;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-pill.highlight {
            border-color: var(--neon-green);
            color: var(--neon-green);
        }

        /* Main Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            flex-grow: 1;
            height: calc(100vh - 100px);
        }

        .main-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            height: 100%;
        }

        .side-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            height: 100%;
        }

        /* Card Panels */
        .panel {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .panel-header h2 {
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-main);
        }

        .panel-header h2 i {
            color: var(--neon-blue);
        }

        /* Courts Grid */
        .courts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1rem;
            flex-grow: 1;
            overflow-y: auto;
        }

        .court-card {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .court-card.completed {
            border-color: var(--neon-green);
        }

        .court-card.active {
            border-color: var(--neon-blue);
        }

        .court-label {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .court-label .status {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
        }

        .court-label .status.active {
            background-color: rgba(14, 165, 233, 0.2);
            color: var(--neon-blue);
        }

        .court-label .status.completed {
            background-color: rgba(34, 197, 94, 0.2);
            color: var(--neon-green);
        }

        .teams-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .team-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 0.75rem;
            border-radius: 0.5rem;
        }

        .team-row.winner {
            border-color: var(--neon-green);
            background-color: rgba(34, 197, 94, 0.05);
        }

        .team-players {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .player-name {
            font-size: 1rem;
            font-weight: 600;
        }

        .vs-divider {
            text-align: center;
            font-weight: 800;
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin: 0.25rem 0;
        }

        /* Queue List */
        .queue-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            overflow-y: auto;
            flex-grow: 1;
            align-content: flex-start;
        }

        .queue-badge {
            background-color: var(--bg-dark);
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .queue-badge span.emoji {
            font-size: 1.1rem;
        }

        .queue-badge span.rating {
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        /* Leaderboard Table */
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leaderboard-table th {
            text-align: left;
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .leaderboard-table td {
            padding: 0.6rem 0.5rem;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .leaderboard-table tr:last-child td {
            border-bottom: none;
        }

        .rank-badge {
            display: inline-flex;
            width: 1.5rem;
            height: 1.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 800;
            font-size: 0.8rem;
        }

        .rank-badge.gold { background-color: var(--neon-yellow); color: var(--bg-dark); }
        .rank-badge.silver { background-color: #cbd5e1; color: var(--bg-dark); }
        .rank-badge.bronze { background-color: #b45309; color: var(--text-main); }
        .rank-badge.other { background-color: var(--border-color); color: var(--text-secondary); }

        .leaderboard-table td.rating {
            font-weight: 800;
            color: var(--neon-green);
        }
    </style>
</head>
<body>

    <header>
        <h1><i class="fas fa-table-tennis text-success"></i> Pickleball <span>Open Play</span></h1>
        <div class="header-meta">
            @if ($event)
                <div class="meta-pill"><i class="far fa-calendar"></i> {{ $event->event_date->format('M d, Y') }}</div>
                <div class="meta-pill"><i class="far fa-clock"></i> {{ \Carbon\Carbon::parse($event->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($event->end_time)->format('g:i A') }}</div>
                @if ($currentRound)
                    <div class="meta-pill highlight"><i class="fas fa-redo"></i> Round {{ $currentRound->round_number }} of {{ $event->rounds }}</div>
                @endif
            @else
                <div class="meta-pill highlight">No Upcoming Session</div>
            @endif
        </div>
    </header>

    @if ($event)
        <div class="dashboard-grid">
            <div class="main-column">
                <!-- Active Courts Panel -->
                <div class="panel" style="flex-grow: 2;">
                    <div class="panel-header">
                        <h2><i class="fas fa-trophy"></i> Live Matches & Assignments</h2>
                        @if ($currentRound)
                            <span style="color: var(--neon-blue); font-weight: 600;">ROUND {{ $currentRound->round_number }}</span>
                        @endif
                    </div>
                    <div class="courts-container">
                        @forelse ($matches as $match)
                            <div class="court-card {{ $match->winner_team !== null ? 'completed' : 'active' }}">
                                <div class="court-label">
                                    <span>{{ $match->court_label }}</span>
                                    @if ($match->winner_team !== null)
                                        <span class="status completed">COMPLETED</span>
                                    @else
                                        <span class="status active">PLAYING</span>
                                    @endif
                                </div>
                                <div class="teams-wrapper">
                                    <div class="team-row {{ (int)$match->winner_team === 1 ? 'winner' : '' }}">
                                        <div class="team-players">
                                            <span class="player-name">{{ $match->team1Player1 ? $match->team1Player1->badge_emoji . ' ' . ($match->team1Player1->endUserProfile ? $match->team1Player1->endUserProfile->first_name . ' ' . $match->team1Player1->endUserProfile->last_name : $match->team1Player1->email) : 'TBD' }}</span>
                                            <span class="player-name">{{ $match->team1Player2 ? $match->team1Player2->badge_emoji . ' ' . ($match->team1Player2->endUserProfile ? $match->team1Player2->endUserProfile->first_name . ' ' . $match->team1Player2->endUserProfile->last_name : $match->team1Player2->email) : 'TBD' }}</span>
                                        </div>
                                    </div>
                                    <div class="vs-divider">VS</div>
                                    <div class="team-row {{ (int)$match->winner_team === 2 ? 'winner' : '' }}">
                                        <div class="team-players">
                                            <span class="player-name">{{ $match->team2Player1 ? $match->team2Player1->badge_emoji . ' ' . ($match->team2Player1->endUserProfile ? $match->team2Player1->endUserProfile->first_name . ' ' . $match->team2Player1->endUserProfile->last_name : $match->team2Player1->email) : 'TBD' }}</span>
                                            <span class="player-name">{{ $match->team2Player2 ? $match->team2Player2->badge_emoji . ' ' . ($match->team2Player2->endUserProfile ? $match->team2Player2->endUserProfile->first_name . ' ' . $match->team2Player2->endUserProfile->last_name : $match->team2Player2->email) : 'TBD' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="grid-column: 1/-1; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">
                                <div>
                                    <i class="fas fa-table-tennis fa-3x mb-2 d-block text-center"></i>
                                    <span>No active matches generated for this round yet.</span>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Waiting Players Queue -->
                <div class="panel" style="flex-grow: 1;">
                    <div class="panel-header">
                        <h2><i class="fas fa-users"></i> Up Next / Waiting Queue ({{ $waitingPlayers->count() }})</h2>
                        <span style="font-size: 0.8rem; color: var(--text-secondary);">Sorted by fair rotation priority</span>
                    </div>
                    <div class="queue-list">
                        @forelse ($waitingPlayers as $player)
                            <div class="queue-badge">
                                <span class="emoji">{{ $player->badge_emoji }}</span>
                                <span>{{ $player->endUserProfile ? $player->endUserProfile->first_name . ' ' . $player->endUserProfile->last_name : $player->email }}</span>
                                <span class="rating">({{ $player->rating }})</span>
                            </div>
                        @empty
                            <div style="width: 100%; text-align: center; color: var(--text-secondary); padding: 1rem 0;">
                                No players currently in waiting queue.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Leaderboard Column -->
            <div class="side-column">
                <div class="panel h-100">
                    <div class="panel-header">
                        <h2><i class="fas fa-star"></i> Open Play Leaderboard</h2>
                    </div>
                    <div style="overflow-y: auto; flex-grow: 1;">
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">Rank</th>
                                    <th>Player</th>
                                    <th style="text-align: right; width: 80px;">Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($leaderboard as $index => $player)
                                    <tr>
                                        <td>
                                            @if ($index === 0)
                                                <span class="rank-badge gold">1</span>
                                            @elseif ($index === 1)
                                                <span class="rank-badge silver">2</span>
                                            @elseif ($index === 2)
                                                <span class="rank-badge bronze">3</span>
                                            @else
                                                <span class="rank-badge other">{{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span style="font-weight: 600;">{{ $player->badge_emoji }} {{ $player->endUserProfile ? $player->endUserProfile->first_name . ' ' . $player->endUserProfile->last_name : $player->email }}</span>
                                            <div style="font-size: 0.7rem; color: var(--text-secondary);">
                                                {{ $player->wins }}W - {{ $player->losses }}L ({{ $player->win_rate }}%)
                                            </div>
                                        </td>
                                        <td class="rating" style="text-align: right;">{{ $player->rating }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--text-secondary); padding: 2rem 0;">
                                            No player rankings available yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div style="flex-grow: 1; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color); border-radius: 1rem; background-color: var(--bg-card);">
            <div style="text-align: center;">
                <i class="fas fa-table-tennis fa-4x mb-3 text-secondary opacity-3"></i>
                <h2>Open Play is Offline</h2>
                <p style="color: var(--text-secondary); margin-top: 0.5rem;">No active Open Play event scheduled right now.</p>
            </div>
        </div>
    @endif

    <script>
        // Refresh the page every 8 seconds for real-time TV updating
        setTimeout(function() {
            window.location.reload();
        }, 8000);
    </script>
</body>
</html>
