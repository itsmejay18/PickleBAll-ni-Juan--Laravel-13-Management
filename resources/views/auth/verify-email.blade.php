<x-guest-layout>
    <p class="text-sm text-secondary mb-4">
        Thanks for signing up. Verify your email address using the link we sent you, or request a new email below.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success text-white text-sm" role="alert">
            A new verification link has been sent to the email address you provided during registration.
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn bg-gradient-info mb-0">Resend Verification Email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link text-dark mb-0">Log Out</button>
        </form>
    </div>
</x-guest-layout>
