<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate($this->rules());
        $operatingHoursJson = $validated['operating_hours_json'] ?? null;
        unset($validated['operating_hours_json']);

        $location = Location::query()->create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name']),
            'country' => $validated['country'] ?? 'Philippines',
            'operating_hours' => $operatingHoursJson
                ? json_decode($operatingHoursJson, true)
                : $this->defaultOperatingHours(),
            'timezone' => $validated['timezone'] ?? 'Asia/Manila',
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
        ]);

        $this->audit->log('location.created', 'locations', $location->id, $request->user(), $validated);

        return back()->with('status', 'Location "'.$location->name.'" created.');
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate($this->rules($location->id));
        $operatingHoursJson = $validated['operating_hours_json'] ?? null;
        unset($validated['operating_hours_json']);

        $location->update([
            ...$validated,
            'operating_hours' => $operatingHoursJson
                ? json_decode($operatingHoursJson, true)
                : $location->operating_hours,
            'is_active' => $request->boolean('is_active', $location->is_active),
        ]);

        $this->audit->log('location.updated', 'locations', $location->id, $request->user(), $validated);

        return back()->with('status', 'Location "'.$location->name.'" updated.');
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        $this->authorizeManage($request);

        $location->delete();
        $this->audit->log('location.deleted', 'locations', $location->id, $request->user(), [
            'name' => $location->name,
        ]);

        return back()->with('status', 'Location archived.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]), 403);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    private function rules(?int $locationId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'branch_code' => ['required', 'string', 'max:20', 'unique:locations,branch_code'.($locationId ? ','.$locationId : '')],
            'address_line1' => ['required', 'string', 'max:500'],
            'address_line2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'landline_number' => ['nullable', 'string', 'max:20'],
            'email_address' => ['nullable', 'email', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'operating_hours_json' => ['nullable', 'string', 'json'],
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Location::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * @return array<string, array{open: string, close: string}>
     */
    private function defaultOperatingHours(): array
    {
        return [
            'monday' => ['open' => '06:00', 'close' => '22:00'],
            'tuesday' => ['open' => '06:00', 'close' => '22:00'],
            'wednesday' => ['open' => '06:00', 'close' => '22:00'],
            'thursday' => ['open' => '06:00', 'close' => '22:00'],
            'friday' => ['open' => '06:00', 'close' => '23:00'],
            'saturday' => ['open' => '07:00', 'close' => '23:00'],
            'sunday' => ['open' => '07:00', 'close' => '21:00'],
        ];
    }
}
