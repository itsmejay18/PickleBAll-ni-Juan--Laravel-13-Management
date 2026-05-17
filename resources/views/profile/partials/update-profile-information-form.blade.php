<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

@php($profilePhotoUrl = $user->photo_path ? asset('storage/'.$user->photo_path) : asset('images/branding.png'))

<form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
    @csrf
    @method('patch')

    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="avatar avatar-xxl bg-white shadow-sm">
            <img src="{{ $profilePhotoUrl }}" alt="{{ $user->email }}">
        </div>
        <div class="flex-grow-1">
            <label for="photo" class="form-label">Profile picture</label>
            <input id="photo" name="photo" type="file" class="form-control @error('photo') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
            <p class="text-xs text-secondary mb-0 mt-1">JPG, PNG, or WebP up to 2 MB.</p>
            @error('photo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="mobile_number" class="form-label">Mobile Number</label>
            <input id="mobile_number" name="mobile_number" type="tel" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number', $user->mobile_number) }}" required autocomplete="tel">
            @error('mobile_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <div class="alert alert-warning text-white text-sm" role="alert">
            Your email address is unverified.
            <button form="send-verification" class="btn btn-link text-white p-0 mb-0 align-baseline">Re-send verification email.</button>
        </div>

        @if (session('status') === 'verification-link-sent')
            <div class="alert alert-success text-white text-sm" role="alert">
                A new verification link has been sent to your email address.
            </div>
        @endif
    @endif

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn bg-gradient-info mb-0">Save</button>

        @if (session('status') === 'profile-updated')
            <span class="text-sm text-secondary" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)">Saved.</span>
        @endif
    </div>
</form>
