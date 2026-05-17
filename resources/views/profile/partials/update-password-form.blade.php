<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="update_password_current_password" class="form-label">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif" autocomplete="current-password">
            @foreach ($errors->updatePassword->get('current_password') as $message)
                <div class="invalid-feedback">{{ $message }}</div>
            @endforeach
        </div>

        <div class="col-md-4 mb-3">
            <label for="update_password_password" class="form-label">New Password</label>
            <input id="update_password_password" name="password" type="password" class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif" autocomplete="new-password">
            @foreach ($errors->updatePassword->get('password') as $message)
                <div class="invalid-feedback">{{ $message }}</div>
            @endforeach
        </div>

        <div class="col-md-4 mb-3">
            <label for="update_password_password_confirmation" class="form-label">Confirm Password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif" autocomplete="new-password">
            @foreach ($errors->updatePassword->get('password_confirmation') as $message)
                <div class="invalid-feedback">{{ $message }}</div>
            @endforeach
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn bg-gradient-info mb-0">Save</button>

        @if (session('status') === 'password-updated')
            <span class="text-sm text-secondary" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)">Saved.</span>
        @endif
    </div>
</form>
