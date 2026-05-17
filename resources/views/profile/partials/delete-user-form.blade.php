<p class="text-sm text-secondary">
    Once your account is deleted, all of its resources and data will be permanently deleted.
</p>

<button type="button" class="btn bg-gradient-danger mb-0" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
    Delete Account
</button>

<div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-labelledby="confirmUserDeletionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="modal-header">
                    <h6 class="modal-title" id="confirmUserDeletionLabel">Delete account</h6>
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="text-sm text-secondary">
                        Please enter your password to confirm permanent account deletion.
                    </p>

                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif" placeholder="Password">
                    @foreach ($errors->userDeletion->get('password') as $message)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @endforeach
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn bg-gradient-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn bg-gradient-danger mb-0">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
