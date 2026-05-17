<x-guest-layout>
    <p class="text-sm text-secondary mb-4">
        Enter your email address and we will send a password reset link.
    </p>

    @if (session('status'))
        <div class="alert alert-success text-white text-sm" role="alert">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="text-end">
            <button type="submit" class="btn bg-gradient-info mb-0">Email Password Reset Link</button>
        </div>
    </form>
</x-guest-layout>
