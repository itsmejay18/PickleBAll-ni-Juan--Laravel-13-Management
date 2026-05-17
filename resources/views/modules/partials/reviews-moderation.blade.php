@php
    /** @var array<int, array<string, mixed>> $ratings */
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <h6 class="mb-0">Review moderation</h6>
                <p class="text-sm mb-0">Approve, hide, respond, or adjust customer reviews. Every action is audited.</p>
            </div>
            <div class="card-body">
                @forelse ($ratings as $rating)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h6 class="mb-1">{{ $rating['title'] ?: 'Review for '.$rating['reservation_code'] }}</h6>
                                <p class="text-xs text-secondary mb-0">
                                    {{ $rating['customer'] }} &middot; Court {{ $rating['court_number'] }} &middot;
                                    <span class="badge badge-sm bg-gradient-{{ $rating['score'] >= 4 ? 'success' : ($rating['score'] >= 3 ? 'info' : 'warning') }}">
                                        {{ $rating['score'] }}/5
                                    </span>
                                </p>
                            </div>
                            <div>
                                <span class="badge badge-sm bg-gradient-{{ $rating['status'] === 'approved' ? 'success' : ($rating['status'] === 'hidden' ? 'secondary' : 'warning') }} text-uppercase">
                                    {{ $rating['status'] }}
                                </span>
                            </div>
                        </div>

                        @if ($rating['comment'])
                            <p class="text-sm mb-2">{{ $rating['comment'] }}</p>
                        @endif

                        @if ($rating['admin_response'])
                            <div class="bg-light p-2 border-radius-md mb-2">
                                <p class="text-xs text-secondary mb-0">Admin response:</p>
                                <p class="text-sm mb-0">{{ $rating['admin_response'] }}</p>
                            </div>
                        @endif

                        <div class="row g-2 mt-2">
                            <div class="col-md-5">
                                <form method="POST" action="{{ route('ratings.respond', $rating['id']) }}" class="d-flex gap-2">
                                    @csrf
                                    <input type="text" name="admin_response" class="form-control form-control-sm" placeholder="Reply to customer..." required maxlength="2000">
                                    <button type="submit" class="btn btn-sm bg-gradient-info mb-0">Reply</button>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <form method="POST" action="{{ route('ratings.adjust', $rating['id']) }}" class="d-flex gap-2">
                                    @csrf
                                    <select name="admin_adjusted_score" class="form-control form-control-sm" required>
                                        @for ($s = 1; $s <= 5; $s++)
                                            <option value="{{ $s }}" @selected($rating['score'] == $s)>{{ $s }} stars</option>
                                        @endfor
                                    </select>
                                    <input type="text" name="admin_adjustment_reason" class="form-control form-control-sm" placeholder="Reason" required maxlength="1000">
                                    <button type="submit" class="btn btn-sm btn-outline-warning mb-0">Adjust</button>
                                </form>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                @if ($rating['status'] !== 'approved')
                                    <form method="POST" action="{{ route('ratings.approve', $rating['id']) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm bg-gradient-success mb-0">Approve</button>
                                    </form>
                                @endif
                                @if ($rating['status'] !== 'hidden')
                                    <form method="POST" action="{{ route('ratings.hide', $rating['id']) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger mb-0">Hide</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-secondary mb-0 text-center py-4">No reviews submitted yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
