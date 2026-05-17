<x-app-layout>
    <x-slot name="header">Profile</x-slot>

    @php($profilePhotoUrl = $user->photo_path ? asset('storage/'.$user->photo_path) : asset('images/branding.png'))

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card card-background card-background-mask-primary pbj-readable-hero h-100">
                <div class="full-background" style="background-image: url('{{ asset('soft-ui-dashboard-main/assets/img/curved-images/curved14.jpg') }}')"></div>
                <div class="card-body position-relative z-index-1 p-4">
                    <div class="avatar avatar-xl bg-white shadow mb-3">
                        <img src="{{ $profilePhotoUrl }}" alt="{{ $user->email }}">
                    </div>
                    <h4 class="text-white font-weight-bolder mb-1">{{ $user->email }}</h4>
                    <p class="text-white pbj-readable-copy mb-0">{{ $user->mobile_number }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Profile Information</h6>
                    <p class="text-sm mb-0">Update your account photo, email address, and mobile number.</p>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Update Password</h6>
                    <p class="text-sm mb-0">Keep the account protected with a strong password.</p>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Danger Zone</h6>
                    <p class="text-sm mb-0">Permanent account actions live here.</p>
                </div>
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
