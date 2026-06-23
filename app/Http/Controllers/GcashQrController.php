<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GcashQrController extends Controller
{
    /**
     * Serve the owner's GCash QR image directly through PHP.
     *
     * On shared hosting (e.g. Hostinger) the `public/storage` symlink is often
     * missing or points outside the document root, so the `/storage/...` URL
     * 404s. Streaming the file through this route works regardless of symlinks
     * or where the web root is mounted.
     */
    public function show(): Response
    {
        $stored = SystemSetting::value('owner_gcash_qr_path');

        abort_if(empty($stored), 404);

        // Stored as Storage::url() output, e.g. "/storage/uploads/abc.png"
        // (or sometimes a full URL). Normalise to a path relative to the
        // public disk root: "uploads/abc.png".
        $relative = Str::of((string) $stored)
            ->after('/storage/')
            ->ltrim('/')
            ->toString();

        $disk = Storage::disk('public');

        abort_unless($relative !== '' && $disk->exists($relative), 404);

        return $disk->response($relative, null, [
            'Cache-Control' => 'public, max-age=60',
        ]);
    }
}
