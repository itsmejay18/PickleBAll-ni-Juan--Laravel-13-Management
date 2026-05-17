<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OperationalDataSeeder extends Seeder
{
    private bool $isSqlite = false;

    /**
     * Seed realistic day-to-day data for the Pickle Ballan ni Juan workflows.
     */
    public function run(): void
    {
        $this->isSqlite = DB::getDriverName() === 'sqlite';

        $users = $this->seedUsers();
        $locations = $this->seedLocations($users);
        $this->seedProfiles($users, $locations);
        $courts = $this->seedCourts($locations, $users);
        $this->seedSchedulesAndMaintenance($courts, $users);
        $this->seedPricing($courts, $users);
        $equipmentTypes = $this->seedEquipmentTypes();
        $this->seedEquipmentInventory($locations, $equipmentTypes, $users);
        $reservations = $this->seedReservations($users, $locations, $courts);
        $this->seedReservationEquipment($reservations, $equipmentTypes, $users, $locations);
        $payments = $this->seedPayments($reservations, $users);
        $this->seedVisitsRatingsAndCancellations($reservations, $payments, $users, $equipmentTypes);
        $this->seedAuditNotificationsAndReports($reservations, $payments, $users, $locations);
        $this->seedSingleVenueWithFourCourts($users);
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $people = [
            'superadmin' => ['Super Admin', 'superadmin@example.com', '09999999991', User::ROLE_SUPER_ADMIN],
            'admin' => ['Admin Ops', 'admin@example.com', '09999999992', User::ROLE_ADMIN],
            'manager' => ['Branch Manager', 'manager@example.com', '09999999993', User::ROLE_LOCATION_MANAGER],
            'staff' => ['Front Desk Staff', 'staff@example.com', '09999999994', User::ROLE_STAFF],
            'frontdesk' => ['Counter Associate', 'frontdesk@example.com', '09999999996', User::ROLE_STAFF],
            'user' => ['Ana Santos', 'user@example.com', '09999999995', User::ROLE_END_USER],
            'maria' => ['Maria Santos', 'maria.santos@example.com', '09170001001', User::ROLE_END_USER],
            'carlo' => ['Carlo Reyes', 'carlo.reyes@example.com', '09170001002', User::ROLE_END_USER],
            'anne' => ['Anne Garcia', 'anne.garcia@example.com', '09170001003', User::ROLE_END_USER],
            'benjie' => ['Benjie Cruz', 'benjie.cruz@example.com', '09170001004', User::ROLE_END_USER],
            'liza' => ['Liza Tan', 'liza.tan@example.com', '09170001005', User::ROLE_END_USER],
        ];

        $users = [];

        foreach ($people as $key => [$name, $email, $mobile, $role]) {
            $user = User::withTrashed()->firstOrNew(['email' => $email]);

            $user->forceFill([
                'email' => $email,
                'mobile_number' => $mobile,
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'last_login_at' => now()->subHours(match ($role) {
                    User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN => 1,
                    User::ROLE_STAFF, User::ROLE_LOCATION_MANAGER => 3,
                    default => 12,
                }),
                'last_login_ip' => '127.0.0.1',
                'login_attempts' => 0,
                'locked_until' => null,
                'deleted_at' => null,
            ])->save();

            $user->syncRoles([$role]);
            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @param  array<string, User>  $users
     * @return array<string, int>
     */
    private function seedLocations(array $users): array
    {
        $hours = json_encode([
            'monday' => ['open' => '06:00', 'close' => '22:00'],
            'tuesday' => ['open' => '06:00', 'close' => '22:00'],
            'wednesday' => ['open' => '06:00', 'close' => '22:00'],
            'thursday' => ['open' => '06:00', 'close' => '22:00'],
            'friday' => ['open' => '06:00', 'close' => '23:00'],
            'saturday' => ['open' => '07:00', 'close' => '23:00'],
            'sunday' => ['open' => '07:00', 'close' => '21:00'],
        ]);

        $locations = [
            'bgc-smash-hub' => [
                'name' => 'BGC Smash Hub',
                'branch_code' => 'BGC01',
                'address_line1' => '2F Rally Building, 9th Avenue',
                'address_line2' => 'Bonifacio Global City',
                'city' => 'Taguig',
                'province' => 'Metro Manila',
                'postal_code' => '1634',
                'latitude' => 14.55027,
                'longitude' => 121.04976,
                'whatsapp_number' => '09171234567',
                'landline_number' => '02-8123-4567',
                'email_address' => 'bgc@pickleballnijuan.test',
                'manager_id' => $users['manager']->id,
                'featured_image_path' => 'soft-ui-dashboard-main/assets/img/home-decor-1.jpg',
            ],
            'qc-rally-center' => [
                'name' => 'QC Rally Center',
                'branch_code' => 'QC02',
                'address_line1' => '88 Scout Rallos Street',
                'address_line2' => 'Diliman',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'postal_code' => '1103',
                'latitude' => 14.63603,
                'longitude' => 121.0437,
                'whatsapp_number' => '09179876543',
                'landline_number' => '02-8988-7788',
                'email_address' => 'qc@pickleballnijuan.test',
                'manager_id' => $users['manager']->id,
                'featured_image_path' => 'soft-ui-dashboard-main/assets/img/home-decor-2.jpg',
            ],
            'cebu-paddle-yard' => [
                'name' => 'Cebu Paddle Yard',
                'branch_code' => 'CEB03',
                'address_line1' => 'Mango Avenue Sports Arcade',
                'address_line2' => 'Camputhaw',
                'city' => 'Cebu City',
                'province' => 'Cebu',
                'postal_code' => '6000',
                'latitude' => 10.31808,
                'longitude' => 123.90192,
                'whatsapp_number' => '09175550123',
                'landline_number' => '032-255-0123',
                'email_address' => 'cebu@pickleballnijuan.test',
                'manager_id' => $users['manager']->id,
                'featured_image_path' => 'soft-ui-dashboard-main/assets/img/home-decor-3.jpg',
            ],
        ];

        foreach ($locations as $slug => $location) {
            DB::table('locations')->updateOrInsert(
                ['slug' => $slug],
                [
                    ...$location,
                    'slug' => $slug,
                    'country' => 'Philippines',
                    'google_maps_embed_url' => 'https://maps.google.com/?q='.urlencode($location['name']),
                    'operating_hours' => $hours,
                    'timezone' => 'Asia/Manila',
                    'is_active' => true,
                    'opening_date' => now()->subMonths(8)->toDateString(),
                    'gallery_images' => json_encode([
                        $location['featured_image_path'],
                        'soft-ui-dashboard-main/assets/img/home-decor-1.jpg',
                    ]),
                    'created_by' => $users['admin']->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );
        }

        return DB::table('locations')->pluck('id', 'slug')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, int>  $locations
     */
    private function seedProfiles(array $users, array $locations): void
    {
        DB::table('admin_profiles')->updateOrInsert(
            ['user_id' => $users['superadmin']->id],
            [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'admin_level' => 'super',
                'permissions' => json_encode(['all']),
                'two_factor_enabled' => false,
                'last_password_change' => now()->subMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        );

        DB::table('admin_profiles')->updateOrInsert(
            ['user_id' => $users['admin']->id],
            [
                'first_name' => 'Ivy',
                'last_name' => 'Saragena',
                'admin_level' => 'full',
                'permissions' => json_encode(['locations', 'payments', 'reports', 'inventory']),
                'two_factor_enabled' => false,
                'last_password_change' => now()->subMonth(),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        );

        $staffProfiles = [
            'manager' => ['Miguel', 'Ramos', 'PBJ-MGR-001', 'Location Manager', $locations['bgc-smash-hub'], true, true, true, 15],
            'staff' => ['Rhea', 'Lopez', 'PBJ-STF-014', 'Court Marshal', $locations['qc-rally-center'], true, false, true, 5],
            'frontdesk' => ['Paolo', 'Lim', 'PBJ-STF-018', 'Front Desk Associate', $locations['cebu-paddle-yard'], true, false, false, 3],
        ];

        foreach ($staffProfiles as $key => [$first, $last, $employeeId, $position, $locationId, $canPay, $canRefund, $canInventory, $discount]) {
            DB::table('staff_profiles')->updateOrInsert(
                ['user_id' => $users[$key]->id],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'employee_id' => $employeeId,
                    'position' => $position,
                    'hire_date' => now()->subMonths(6)->toDateString(),
                    'termination_date' => null,
                    'assigned_location_id' => $locationId,
                    'can_confirm_payments' => $canPay,
                    'can_process_refunds' => $canRefund,
                    'can_manage_inventory' => $canInventory,
                    'max_discount_percentage' => $discount,
                    'hourly_rate' => 145,
                    'schedule_preferences' => json_encode(['shift' => 'opening', 'days' => ['mon', 'tue', 'wed', 'thu', 'fri']]),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );
        }

        $customers = [
            'user' => ['Ana', 'Santos', 7, 9200, 1840],
            'maria' => ['Maria', 'Santos', 14, 24650, 4930],
            'carlo' => ['Carlo', 'Reyes', 5, 7500, 1500],
            'anne' => ['Anne', 'Garcia', 11, 18300, 3660],
            'benjie' => ['Benjie', 'Cruz', 3, 3900, 780],
            'liza' => ['Liza', 'Tan', 4, 5600, 1120],
        ];

        foreach ($customers as $key => [$first, $last, $bookings, $spent, $points]) {
            DB::table('end_user_profiles')->updateOrInsert(
                ['user_id' => $users[$key]->id],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'birth_date' => now()->subYears(28)->subDays($bookings)->toDateString(),
                    'gender' => 'prefer_not_to_say',
                    'emergency_contact_name' => $first.' Contact',
                    'emergency_contact_number' => '0917555'.str_pad((string) $bookings, 4, '0', STR_PAD_LEFT),
                    'preferred_language' => 'en',
                    'notification_preferences' => json_encode(['email' => true, 'sms' => true, 'in_app' => true]),
                    'total_bookings' => $bookings,
                    'total_spent' => $spent,
                    'loyalty_points' => $points,
                    'last_active_at' => now()->subHours($bookings % 6 + 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * @param  array<string, int>  $locations
     * @param  array<string, User>  $users
     * @return array<string, int>
     */
    private function seedCourts(array $locations, array $users): array
    {
        $courts = [
            ['bgc-smash-hub', 'A', 'BGC Center Court', 'indoor', 'acrylic', true, true, true, true],
            ['bgc-smash-hub', 'B', 'BGC Training Court', 'indoor', 'acrylic', true, true, true, true],
            ['bgc-smash-hub', 'C', 'BGC Challenge Court', 'covered', 'concrete', true, true, true, false],
            ['qc-rally-center', 'A', 'QC North Court', 'covered', 'acrylic', true, true, true, false],
            ['qc-rally-center', 'B', 'QC South Court', 'outdoor', 'concrete', true, true, true, false],
            ['cebu-paddle-yard', 'A', 'Cebu Premier Court', 'indoor', 'acrylic', true, true, true, true],
            ['cebu-paddle-yard', 'B', 'Cebu Garden Court', 'outdoor', 'asphalt', true, true, true, false],
        ];

        foreach ($courts as $index => [$slug, $number, $name, $type, $surface, $lighting, $net, $seating, $aircon]) {
            DB::table('courts')->updateOrInsert(
                ['location_id' => $locations[$slug], 'court_number' => $number],
                [
                    'court_name' => $name,
                    'court_type' => $type,
                    'surface_type' => $surface,
                    'width' => 6.10,
                    'length' => 13.41,
                    'has_lighting' => $lighting,
                    'has_net' => $net,
                    'has_seating' => $seating,
                    'has_shade' => $type !== 'outdoor',
                    'is_airconditioned' => $aircon,
                    'is_active' => true,
                    'display_order' => $index + 1,
                    'description' => $name.' is regulation size and ready for singles or doubles play.',
                    'special_instructions' => 'Non-marking court shoes required.',
                    'created_by' => $users['admin']->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );
        }

        $courtIds = DB::table('courts')
            ->join('locations', 'locations.id', '=', 'courts.location_id')
            ->select('courts.id', 'locations.slug', 'courts.court_number')
            ->get()
            ->mapWithKeys(fn ($court) => [$court->slug.':'.$court->court_number => (int) $court->id])
            ->all();

        foreach ($courtIds as $key => $courtId) {
            DB::table('court_images')->updateOrInsert(
                ['court_id' => $courtId, 'image_path' => 'soft-ui-dashboard-main/assets/img/home-decor-1.jpg'],
                [
                    'image_filename' => str_replace(':', '-', $key).'.jpg',
                    'image_size' => 245000,
                    'mime_type' => 'image/jpeg',
                    'alt_text' => 'Pickleball court '.$key,
                    'caption' => 'Prepared court photo for '.$key,
                    'is_primary' => true,
                    'display_order' => 1,
                    'uploaded_by' => $users['admin']->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );
        }

        return $courtIds;
    }

    /**
     * @param  array<string, int>  $courts
     * @param  array<string, User>  $users
     */
    private function seedSchedulesAndMaintenance(array $courts, array $users): void
    {
        foreach ($courts as $courtId) {
            foreach (range(0, 6) as $day) {
                DB::table('court_schedules')->updateOrInsert(
                    ['court_id' => $courtId, 'day_of_week' => $day],
                    [
                        'open_time' => in_array($day, [0, 6], true) ? '07:00:00' : '06:00:00',
                        'close_time' => in_array($day, [5, 6], true) ? '23:00:00' : '22:00:00',
                        'break_start_time' => '12:00:00',
                        'break_end_time' => '12:30:00',
                        'is_available' => true,
                        'effective_from' => now()->subMonths(6)->toDateString(),
                        'effective_to' => null,
                        'created_by' => $users['admin']->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        $maintenance = [
            ['qc-rally-center:B', 'Net tension inspection', 'regular', $this->todayAt('15:00'), $this->todayAt('16:00'), 'Routine net and sideline inspection.'],
            ['bgc-smash-hub:C', 'Weekend mini tournament setup', 'tournament', now()->addDays(2)->setTime(8, 0), now()->addDays(2)->setTime(12, 0), 'Court blocked for community ladder setup.'],
            ['cebu-paddle-yard:B', 'Surface drying window', 'emergency', $this->todayAt('11:00'), $this->todayAt('12:30'), 'Outdoor court drying after morning cleaning.'],
        ];

        foreach ($maintenance as [$courtKey, $title, $type, $start, $end, $description]) {
            DB::table('court_maintenance')->updateOrInsert(
                ['court_id' => $courts[$courtKey], 'title' => $title],
                [
                    'maintenance_type' => $type,
                    'description' => $description,
                    'start_datetime' => $start,
                    'end_datetime' => $end,
                    'is_all_day' => false,
                    'recurring_weekly' => false,
                    'recurring_end_date' => null,
                    'approved_by' => $users['admin']->id,
                    'created_by' => $users['staff']->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * @param  array<string, int>  $courts
     * @param  array<string, User>  $users
     */
    private function seedPricing(array $courts, array $users): void
    {
        foreach ($courts as $courtKey => $courtId) {
            $base = str_contains($courtKey, 'bgc') ? 750 : (str_contains($courtKey, 'qc') ? 650 : 600);

            $rules = [
                ['Weekday Standard', null, '06:00:00', '17:00:00', false, false, $base, 0, 1],
                ['Evening Peak', null, '17:00:00', '23:00:00', false, false, $base, 20, 5],
                ['Weekend Prime', null, '07:00:00', '23:00:00', false, true, $base + 100, 15, 10],
            ];

            foreach ($rules as [$name, $day, $start, $end, $holiday, $peak, $price, $surcharge, $priority]) {
                DB::table('court_pricing_rules')->updateOrInsert(
                    ['court_id' => $courtId, 'rule_name' => $name],
                    [
                        'day_of_week' => $day,
                        'start_time' => $start,
                        'end_time' => $end,
                        'is_holiday' => $holiday,
                        'is_peak_season' => $peak,
                        'base_price' => $price,
                        'peak_surcharge_percentage' => $surcharge,
                        'minimum_hours' => 1,
                        'maximum_hours' => 4,
                        'priority' => $priority,
                        'is_active' => true,
                        'effective_from' => now()->subMonths(6)->toDateString(),
                        'effective_to' => null,
                        'created_by' => $users['admin']->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function seedEquipmentTypes(): array
    {
        $types = [
            'premium-paddle' => ['Premium Paddle Rental', 'Graphite paddle rental for club players.', 150, 500, 50, 2500, true, true, 4, 1],
            'training-ball-tube' => ['Training Ball Tube', 'Tube of outdoor-ready balls for warmups.', 80, 0, 20, 600, true, false, 6, 2],
            'net-kit' => ['Portable Net Kit', 'Portable net add-on for clinics and events.', 350, 1200, 100, 4500, true, true, 1, 3],
        ];

        foreach ($types as $slug => [$name, $description, $rental, $deposit, $lateFee, $replacement, $available, $requiresDeposit, $max, $order]) {
            DB::table('equipment_types')->updateOrInsert(
                ['slug' => $slug],
                [
                    'category_id' => null,
                    'name' => $name,
                    'description' => $description,
                    'rental_price_per_unit' => $rental,
                    'deposit_amount' => $deposit,
                    'late_fee_per_hour' => $lateFee,
                    'damage_replacement_cost' => $replacement,
                    'is_available_for_rent' => $available,
                    'requires_deposit' => $requiresDeposit,
                    'max_rental_quantity_per_booking' => $max,
                    'display_order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );
        }

        return DB::table('equipment_types')->pluck('id', 'slug')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<string, int>  $locations
     * @param  array<string, int>  $equipmentTypes
     * @param  array<string, User>  $users
     */
    private function seedEquipmentInventory(array $locations, array $equipmentTypes, array $users): void
    {
        $rows = [
            ['bgc-smash-hub', 'premium-paddle', 32, 23, 6, 2, 1, 0, 6],
            ['bgc-smash-hub', 'training-ball-tube', 48, 35, 9, 2, 0, 2, 8],
            ['bgc-smash-hub', 'net-kit', 4, 3, 1, 0, 0, 0, 1],
            ['qc-rally-center', 'premium-paddle', 24, 15, 5, 1, 1, 2, 5],
            ['qc-rally-center', 'training-ball-tube', 36, 24, 7, 1, 0, 4, 6],
            ['qc-rally-center', 'net-kit', 3, 2, 1, 0, 0, 0, 1],
            ['cebu-paddle-yard', 'premium-paddle', 18, 9, 5, 2, 1, 1, 6],
            ['cebu-paddle-yard', 'training-ball-tube', 30, 13, 10, 3, 0, 4, 8],
            ['cebu-paddle-yard', 'net-kit', 2, 1, 1, 0, 0, 0, 1],
        ];

        foreach ($rows as [$locationSlug, $equipmentSlug, $total, $available, $reserved, $damaged, $lost, $maintenance, $reorder]) {
            DB::table('equipment_inventory')->updateOrInsert(
                ['location_id' => $locations[$locationSlug], 'equipment_type_id' => $equipmentTypes[$equipmentSlug]],
                [
                    'total_quantity' => $total,
                    'available_quantity' => $available,
                    'reserved_quantity' => $reserved,
                    'damaged_quantity' => $damaged,
                    'lost_quantity' => $lost,
                    'under_maintenance_quantity' => $maintenance,
                    'last_inventory_count_at' => now()->subDay(),
                    'last_inventory_count_by' => $users['staff']->id,
                    'reorder_point' => $reorder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, int>  $locations
     * @param  array<string, int>  $courts
     * @return array<string, int>
     */
    private function seedReservations(array $users, array $locations, array $courts): array
    {
        $today = now()->toDateString();
        $reservations = [
            ['PBJ-BGC-1001', 'maria', 'bgc-smash-hub:A', 'bgc-smash-hub', $today, '08:00:00', '10:00:00', 750, 1500, 300, 0, 0, 1800, 'online', 'confirmed', 'paid', 'Prefers Court A end line side.', -1, 'admin'],
            ['PBJ-BGC-1002', 'carlo', 'bgc-smash-hub:B', 'bgc-smash-hub', $today, '10:00:00', '12:00:00', 750, 1500, 160, 0, 0, 1660, 'online', 'payment_verification', 'pending_verification', 'Uploaded GCash proof, waiting for review.', -1, null],
            ['PBJ-QC-2001', 'user', 'qc-rally-center:A', 'qc-rally-center', $today, '13:00:00', '14:30:00', 650, 975, 150, 0, 0, 1125, 'online', 'checked_in', 'paid', 'Birthday doubles match.', -1, 'staff'],
            ['PBJ-QC-2002', 'benjie', 'qc-rally-center:B', 'qc-rally-center', $today, '16:00:00', '18:00:00', 650, 1300, 0, 0, 0, 1300, 'walk_in', 'confirmed', 'paid', 'Counter booking for four players.', 0, 'staff'],
            ['PBJ-CEB-3001', 'liza', 'cebu-paddle-yard:A', 'cebu-paddle-yard', $today, '19:00:00', '21:00:00', 600, 1200, 300, 0, 0, 1500, 'online', 'pending_payment', 'unpaid', 'Needs two paddles.', 0, null],
            ['PBJ-BGC-1003', 'anne', 'bgc-smash-hub:C', 'bgc-smash-hub', now()->subDay()->toDateString(), '18:00:00', '20:00:00', 750, 1500, 160, 0, 0, 1660, 'online', 'completed', 'paid', 'Evening session with ball tube.', -2, 'admin'],
            ['PBJ-CEB-3002', 'carlo', 'cebu-paddle-yard:B', 'cebu-paddle-yard', now()->subDay()->toDateString(), '09:00:00', '11:00:00', 600, 1200, 0, 0, 0, 1200, 'online', 'cancelled', 'refunded', 'Customer requested cancellation.', -3, 'admin'],
            ['PBJ-QC-2003', 'maria', 'qc-rally-center:A', 'qc-rally-center', now()->addDay()->toDateString(), '09:00:00', '11:00:00', 650, 1300, 300, 0, 0, 1600, 'online', 'confirmed', 'paid', 'Morning practice.', -1, 'staff'],
            ['PBJ-BGC-1004', 'user', 'bgc-smash-hub:A', 'bgc-smash-hub', now()->subDays(2)->toDateString(), '07:00:00', '09:00:00', 750, 1500, 300, 100, 0, 1700, 'walk_in', 'completed', 'paid', 'Walk-in clinic with rented paddles.', -3, 'frontdesk'],
            ['PBJ-BGC-1005', 'benjie', 'bgc-smash-hub:B', 'bgc-smash-hub', now()->addDay()->toDateString(), '18:00:00', '20:00:00', 900, 1800, 160, 0, 0, 1960, 'online', 'payment_verification', 'pending_verification', 'Peak-hour booking proof under review.', 0, null],
            ['PBJ-BGC-1006', 'user', 'bgc-smash-hub:B', 'bgc-smash-hub', now()->addDays(2)->toDateString(), '14:00:00', '16:00:00', 750, 1500, 150, 0, 0, 1650, 'online', 'pending_payment', 'unpaid', 'Demo booking ready for GCash screenshot upload.', 0, null],
            ['PBJ-QC-2004', 'anne', 'qc-rally-center:B', 'qc-rally-center', now()->subDays(5)->toDateString(), '18:00:00', '20:00:00', 650, 1300, 0, 0, 0, 1300, 'online', 'no_show', 'paid', 'No arrival after grace period.', -6, 'staff'],
        ];

        foreach ($reservations as [$code, $userKey, $courtKey, $locationSlug, $date, $start, $end, $rate, $courtSubtotal, $equipment, $discount, $tax, $total, $type, $status, $paymentStatus, $requests, $createdOffsetDays, $confirmedBy]) {
            $createdAt = now()->addDays($createdOffsetDays)->setTime(9, 0);
            $confirmedAt = in_array($paymentStatus, ['paid', 'refunded'], true) ? $createdAt->copy()->addHours(2) : null;

            $payload = [
                'user_id' => $users[$userKey]->id,
                'court_id' => $courts[$courtKey],
                'location_id' => $locations[$locationSlug],
                'reservation_date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'court_price_per_hour' => $rate,
                'court_subtotal' => $courtSubtotal,
                'equipment_total' => $equipment,
                'discount_amount' => $discount,
                'discount_type' => $discount > 0 ? 'fixed' : null,
                'discount_reason' => $discount > 0 ? 'Loyalty voucher' : null,
                'tax_amount' => $tax,
                'tax_rate' => 0,
                'grand_total' => $total,
                'reservation_type' => $type,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'special_requests' => $requests,
                'is_active' => ! in_array($status, ['cancelled', 'refunded'], true),
                'confirmed_at' => $confirmedAt,
                'confirmed_by' => $confirmedBy ? $users[$confirmedBy]->id : null,
                'created_ip' => '127.0.0.1',
                'user_agent' => 'Pickle Ballan ni Juan seeder',
                'created_at' => $createdAt,
                'updated_at' => now(),
                'deleted_at' => null,
            ];

            if ($this->isSqlite) {
                $payload['total_hours'] = $this->hoursBetween($date, $start, $end);
                $payload['expires_at'] = $createdAt->copy()->addHours(2);
            }

            DB::table('reservations')->updateOrInsert(
                ['reservation_code' => $code],
                $payload,
            );
        }

        return DB::table('reservations')->pluck('id', 'reservation_code')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<string, int>  $reservations
     * @param  array<string, int>  $equipmentTypes
     * @param  array<string, User>  $users
     * @param  array<string, int>  $locations
     */
    private function seedReservationEquipment(array $reservations, array $equipmentTypes, array $users, array $locations): void
    {
        $items = [
            ['PBJ-BGC-1001', 'premium-paddle', 2, 150, 1000, false],
            ['PBJ-BGC-1002', 'training-ball-tube', 2, 80, 0, false],
            ['PBJ-QC-2001', 'premium-paddle', 1, 150, 500, false],
            ['PBJ-CEB-3001', 'premium-paddle', 2, 150, 1000, false],
            ['PBJ-BGC-1003', 'training-ball-tube', 2, 80, 0, true],
            ['PBJ-QC-2003', 'premium-paddle', 2, 150, 1000, false],
            ['PBJ-BGC-1004', 'premium-paddle', 2, 150, 1000, true],
            ['PBJ-BGC-1005', 'training-ball-tube', 2, 80, 0, false],
            ['PBJ-BGC-1006', 'premium-paddle', 1, 150, 500, false],
        ];

        foreach ($items as [$code, $equipmentSlug, $quantity, $price, $deposit, $returned]) {
            $payload = [
                'quantity' => $quantity,
                'price_per_unit' => $price,
                'deposit_charged' => $deposit,
                'deposit_returned' => $returned,
                'deposit_returned_at' => $returned ? now()->subDay() : null,
                'is_returned' => $returned,
                'returned_at' => $returned ? now()->subDay() : null,
                'returned_to_staff_id' => $returned ? $users['staff']->id : null,
                'damage_notes' => $code === 'PBJ-BGC-1004' ? 'One paddle grip needed replacement; loyalty voucher covered charge.' : null,
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ];

            if ($this->isSqlite) {
                $payload['subtotal'] = $quantity * $price;
            }

            DB::table('reservation_equipment')->updateOrInsert(
                ['reservation_id' => $reservations[$code], 'equipment_type_id' => $equipmentTypes[$equipmentSlug]],
                $payload,
            );
        }

        $transactions = [
            ['bgc-smash-hub', 'premium-paddle', 'PBJ-BGC-1001', 'check_out', -2, 25, 23, 'Reserved two paddles for confirmed booking.'],
            ['qc-rally-center', 'premium-paddle', 'PBJ-QC-2001', 'check_out', -1, 16, 15, 'Released paddle during check-in.'],
            ['bgc-smash-hub', 'premium-paddle', 'PBJ-BGC-1004', 'check_in', 2, 21, 23, 'Returned walk-in clinic paddles.'],
            ['bgc-smash-hub', 'premium-paddle', 'PBJ-BGC-1004', 'damaged', -1, 23, 22, 'Grip damage recorded on return.'],
            ['cebu-paddle-yard', 'training-ball-tube', null, 'count_adjustment', -2, 15, 13, 'Adjusted after weekly inventory count.'],
        ];

        foreach ($transactions as [$locationSlug, $equipmentSlug, $reservationCode, $type, $quantity, $previous, $new, $notes]) {
            DB::table('inventory_transactions')->updateOrInsert(
                [
                    'location_id' => $locations[$locationSlug],
                    'equipment_type_id' => $equipmentTypes[$equipmentSlug],
                    'reservation_id' => $reservationCode ? $reservations[$reservationCode] : null,
                    'transaction_type' => $type,
                ],
                [
                    'quantity' => $quantity,
                    'previous_available' => $previous,
                    'new_available' => $new,
                    'notes' => $notes,
                    'performed_by' => $users['staff']->id,
                    'created_at' => now()->subHours(6),
                ],
            );
        }
    }

    /**
     * @param  array<string, int>  $reservations
     * @param  array<string, User>  $users
     * @return array<string, int>
     */
    private function seedPayments(array $reservations, array $users): array
    {
        $payments = [
            ['PAY-BGC-1001', 'PBJ-BGC-1001', 1800, 'gcash', 'verified', 'GC-843921551', '09170001001', 'admin', -1],
            ['PAY-BGC-1002', 'PBJ-BGC-1002', 1660, 'gcash', 'pending', 'GC-843921552', '09170001002', null, 0],
            ['PAY-QC-2001', 'PBJ-QC-2001', 1125, 'gcash', 'verified', 'GC-843921553', '09999999995', 'staff', 0],
            ['CASH-QC-2002', 'PBJ-QC-2002', 1300, 'cash', 'verified', null, null, 'staff', 0],
            ['PAY-BGC-1003', 'PBJ-BGC-1003', 1660, 'gcash', 'verified', 'GC-843921554', '09170001003', 'admin', -2],
            ['PAY-CEB-3002', 'PBJ-CEB-3002', 1200, 'gcash', 'refunded', 'GC-843921555', '09170001002', 'admin', -3],
            ['PAY-QC-2003', 'PBJ-QC-2003', 1600, 'gcash', 'verified', 'GC-843921556', '09170001001', 'staff', -1],
            ['CASH-BGC-1004', 'PBJ-BGC-1004', 1700, 'cash', 'verified', null, null, 'frontdesk', -3],
            ['PAY-BGC-1005', 'PBJ-BGC-1005', 1960, 'gcash', 'pending', 'GC-843921557', '09170001004', null, 0],
            ['PAY-QC-2004', 'PBJ-QC-2004', 1300, 'gcash', 'verified', 'GC-843921558', '09170001003', 'staff', -6],
        ];

        foreach ($payments as [$reference, $reservationCode, $amount, $method, $status, $gcashReference, $senderNumber, $verifiedBy, $offset]) {
            $createdAt = now()->addDays($offset)->setTime(10, 0);
            $verifiedAt = in_array($status, ['verified', 'refunded'], true) ? $createdAt->copy()->addMinutes(45) : null;

            DB::table('payments')->updateOrInsert(
                ['payment_reference' => $reference],
                [
                    'reservation_id' => $reservations[$reservationCode],
                    'amount' => $amount,
                    'payment_method' => $method,
                    'payment_type' => 'full',
                    'gcash_number_sent_to' => $method === 'gcash' ? '09123456789' : null,
                    'gcash_sender_number' => $senderNumber,
                    'gcash_reference_number' => $gcashReference,
                    'gcash_screenshot_path' => $method === 'gcash' ? 'payments/screenshots/'.$reference.'.jpg' : null,
                    'cash_received_amount' => $method === 'cash' ? $amount + 200 : null,
                    'cash_change_amount' => $method === 'cash' ? 200 : null,
                    'status' => $status,
                    'verified_by' => $verifiedBy ? $users[$verifiedBy]->id : null,
                    'verified_at' => $verifiedAt,
                    'rejection_reason' => null,
                    'refunded_at' => $status === 'refunded' ? now()->subDay() : null,
                    'refunded_by' => $status === 'refunded' ? $users['admin']->id : null,
                    'refund_reason' => $status === 'refunded' ? 'Cancellation approved within policy window.' : null,
                    'notes' => $status === 'pending' ? 'Awaiting screenshot/reference verification.' : 'Seeded operational payment.',
                    'created_by' => $method === 'cash' ? $users[$verifiedBy ?: 'staff']->id : DB::table('reservations')->where('id', $reservations[$reservationCode])->value('user_id'),
                    'created_at' => $createdAt,
                    'updated_at' => now(),
                ],
            );
        }

        $ids = DB::table('payments')->pluck('id', 'payment_reference')->map(fn ($id) => (int) $id)->all();

        foreach ($payments as [$reference, $reservationCode, $amount, $method, $status, $gcashReference, $senderNumber, $verifiedBy]) {
            if ($method !== 'gcash') {
                continue;
            }

            DB::table('gcash_transactions_log')->updateOrInsert(
                ['reference_number' => $gcashReference],
                [
                    'payment_id' => $ids[$reference],
                    'user_id' => DB::table('reservations')->where('id', $reservations[$reservationCode])->value('user_id'),
                    'owner_gcash_number' => '09123456789',
                    'user_sent_from_number' => $senderNumber,
                    'screenshot_path' => 'payments/screenshots/'.$reference.'.jpg',
                    'amount' => $amount,
                    'status' => match ($status) {
                        'verified', 'refunded' => 'verified',
                        'rejected' => 'rejected',
                        default => 'pending_verification',
                    },
                    'verification_notes' => $status === 'pending' ? 'Clear proof pending staff review.' : 'Reference matched amount and sender.',
                    'verified_by' => $verifiedBy ? $users[$verifiedBy]->id : null,
                    'verified_at' => $verifiedBy ? now()->subHours(2) : null,
                    'dispute_reason' => null,
                    'resolved_by' => null,
                    'resolved_at' => null,
                    'ip_address' => '127.0.0.1',
                    'created_at' => now()->subHours(4),
                    'updated_at' => now(),
                ],
            );
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $reservations
     * @param  array<string, int>  $payments
     * @param  array<string, User>  $users
     * @param  array<string, int>  $equipmentTypes
     */
    private function seedVisitsRatingsAndCancellations(array $reservations, array $payments, array $users, array $equipmentTypes): void
    {
        $checkIns = [
            ['PBJ-QC-2001', 'staff', now()->setTime(12, 52), '12:58:00', [['item' => 'Premium Paddle Rental', 'qty' => 1]]],
            ['PBJ-BGC-1003', 'staff', now()->subDay()->setTime(17, 50), '18:00:00', [['item' => 'Training Ball Tube', 'qty' => 2]]],
            ['PBJ-BGC-1004', 'frontdesk', now()->subDays(2)->setTime(6, 55), '07:00:00', [['item' => 'Premium Paddle Rental', 'qty' => 2]]],
        ];

        foreach ($checkIns as [$code, $staffKey, $checkedInAt, $actualStart, $equipment]) {
            DB::table('check_in_logs')->updateOrInsert(
                ['reservation_id' => $reservations[$code]],
                [
                    'checked_in_by' => $users[$staffKey]->id,
                    'checked_in_at' => $checkedInAt,
                    'check_in_method' => 'staff_search',
                    'actual_start_time' => $actualStart,
                    'customer_show_proof' => true,
                    'verified_via' => 'digital_receipt',
                    'equipment_released' => json_encode($equipment),
                    'notes' => 'Customer verified and court assignment confirmed.',
                    'created_at' => $checkedInAt,
                ],
            );
        }

        $checkOuts = [
            ['PBJ-BGC-1003', 'staff', now()->subDay()->setTime(20, 5), '20:00:00', [['item' => 'Training Ball Tube', 'qty' => 2]], [], [], 0, 0, true, 'Great evening slot.'],
            ['PBJ-BGC-1004', 'frontdesk', now()->subDays(2)->setTime(9, 7), '09:00:00', [['item' => 'Premium Paddle Rental', 'qty' => 1]], [['item' => 'Premium Paddle Rental', 'qty' => 1, 'note' => 'Grip scuffed']], [], 100, 0, true, 'Staff handled damage politely.'],
        ];

        foreach ($checkOuts as [$code, $staffKey, $checkedOutAt, $actualEnd, $returned, $damaged, $lost, $damageCharges, $lateCharges, $paidAdditional, $feedback]) {
            DB::table('check_out_logs')->updateOrInsert(
                ['reservation_id' => $reservations[$code]],
                [
                    'checked_out_by' => $users[$staffKey]->id,
                    'checked_out_at' => $checkedOutAt,
                    'actual_end_time' => $actualEnd,
                    'equipment_returned' => json_encode($returned),
                    'equipment_damaged' => json_encode($damaged),
                    'equipment_lost' => json_encode($lost),
                    'damage_charges' => $damageCharges,
                    'late_charges' => $lateCharges,
                    'total_additional_charges' => $damageCharges + $lateCharges,
                    'customer_paid_additional' => $paidAdditional,
                    'payment_collected_by' => $paidAdditional ? $users[$staffKey]->id : null,
                    'feedback_from_customer' => $feedback,
                    'rating_reminder_sent' => true,
                    'created_at' => $checkedOutAt,
                ],
            );
        }

        $ratings = [
            ['PBJ-BGC-1003', 'anne', 5, 'Fast confirmation and clean court', 'Court C was ready on time and the staff helped with the ball tube.', true, 'approved', 8, 'Thanks, Anne. See you again this weekend.'],
            ['PBJ-BGC-1004', 'user', 4, 'Good clinic experience', 'The front desk explained the extra charge clearly.', true, 'approved', 3, 'Appreciate the feedback. We replaced the grip already.'],
            ['PBJ-QC-2004', 'anne', 3, 'Missed schedule', 'I missed the booking but still found the receipt easy to review.', false, 'pending', 0, null],
        ];

        foreach ($ratings as [$reservationCode, $userKey, $score, $title, $comment, $recommend, $status, $helpful, $response]) {
            $reservation = DB::table('reservations')->where('id', $reservations[$reservationCode])->first();

            DB::table('ratings')->updateOrInsert(
                ['reservation_id' => $reservations[$reservationCode]],
                [
                    'user_id' => $users[$userKey]->id,
                    'court_id' => $reservation->court_id,
                    'rating_score' => $score,
                    'review_title' => $title,
                    'review_comment' => $comment,
                    'categories' => json_encode(['court_quality' => $score, 'staff' => min(5, $score + 1), 'value' => $score]),
                    'would_recommend' => $recommend,
                    'is_verified_purchase' => true,
                    'status' => $status,
                    'helpful_count' => $helpful,
                    'not_helpful_count' => 0,
                    'admin_response' => $response,
                    'admin_responded_by' => $response ? $users['admin']->id : null,
                    'admin_responded_at' => $response ? now()->subHours(12) : null,
                    'original_rating_score' => null,
                    'admin_adjusted_score' => null,
                    'admin_adjustment_reason' => null,
                    'admin_adjusted_by' => null,
                    'admin_adjusted_at' => null,
                    'created_at' => now()->subHours(18),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );
        }

        $ratingIds = DB::table('ratings')->pluck('id', 'reservation_id')->map(fn ($id) => (int) $id)->all();
        DB::table('rating_helpful_votes')->updateOrInsert(
            ['rating_id' => $ratingIds[$reservations['PBJ-BGC-1003']], 'user_id' => $users['maria']->id],
            ['is_helpful' => true, 'created_at' => now()->subHours(4)],
        );

        $policyId = DB::table('cancellation_policies')->where('policy_name', 'Full Refund - 24+ hours')->value('id');

        DB::table('cancellation_logs')->updateOrInsert(
            ['reservation_id' => $reservations['PBJ-CEB-3002']],
            [
                'cancelled_by' => $users['carlo']->id,
                'cancelled_at' => now()->subDay()->setTime(7, 0),
                'cancellation_policy_id' => $policyId,
                'refund_amount' => 1200,
                'refund_payment_id' => $payments['PAY-CEB-3002'],
                'reason_category' => 'customer_request',
                'reason_text' => 'Customer had a schedule conflict and cancelled before the policy cutoff.',
                'customer_notified' => true,
                'notification_sent_at' => now()->subDay()->setTime(7, 5),
                'notes' => 'Refund processed through GCash.',
                'created_at' => now()->subDay()->setTime(7, 0),
            ],
        );
    }

    /**
     * @param  array<string, int>  $reservations
     * @param  array<string, int>  $payments
     * @param  array<string, User>  $users
     * @param  array<string, int>  $locations
     */
    private function seedAuditNotificationsAndReports(array $reservations, array $payments, array $users, array $locations): void
    {
        $templates = [
            ['booking_confirmed', 'Booking Confirmed', 'email', 'Your Pickle Ballan ni Juan booking is confirmed', 'Reservation {{reservation_code}} is confirmed for {{reservation_date}}.'],
            ['payment_received', 'Payment Received', 'sms', null, 'Payment {{payment_reference}} was received and verified.'],
            ['checkin_reminder', 'Check-in Reminder', 'in_app', 'Your court is ready soon', 'Please arrive 15 minutes before your court time.'],
        ];

        foreach ($templates as [$code, $name, $channel, $subject, $body]) {
            DB::table('notification_templates')->updateOrInsert(
                ['template_code' => $code],
                [
                    'template_name' => $name,
                    'channel' => $channel,
                    'subject_template' => $subject,
                    'body_template' => $body,
                    'variables' => json_encode(['reservation_code', 'reservation_date', 'payment_reference']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $notifications = [
            ['PBJ-BGC-1001', 'maria', 'email', 'booking', 'Booking PBJ-BGC-1001 confirmed', 'sent'],
            ['PBJ-BGC-1002', 'carlo', 'in_app', 'payment', 'Payment proof queued for review', 'delivered'],
            ['PBJ-QC-2001', 'user', 'sms', 'checkin', 'Check-in completed for PBJ-QC-2001', 'sent'],
            ['PBJ-CEB-3002', 'carlo', 'email', 'refund', 'Refund processed for PBJ-CEB-3002', 'sent'],
        ];

        foreach ($notifications as [$code, $userKey, $type, $channel, $subject, $status]) {
            DB::table('notification_logs')->updateOrInsert(
                ['reservation_id' => $reservations[$code], 'subject' => $subject],
                [
                    'user_id' => $users[$userKey]->id,
                    'notification_type' => $type === 'booking' || $type === 'refund' ? 'email' : $type,
                    'channel' => $channel,
                    'recipient' => $users[$userKey]->email,
                    'message' => 'Automated notification for '.$code.'.',
                    'status' => $status,
                    'sent_at' => now()->subHours(3),
                    'delivered_at' => $status !== 'failed' ? now()->subHours(2) : null,
                    'read_at' => $status === 'delivered' ? now()->subHour() : null,
                    'error_message' => null,
                    'retry_count' => 0,
                    'metadata' => json_encode(['reservation_code' => $code]),
                    'created_at' => now()->subHours(3),
                    'updated_at' => now(),
                ],
            );
        }

        $audits = [
            ['payment.verified', 'payments', $payments['PAY-BGC-1001'], 'Verified GCash payment for PBJ-BGC-1001', 'admin'],
            ['reservation.checked_in', 'reservations', $reservations['PBJ-QC-2001'], 'Checked in Ana Santos at QC Rally Center', 'staff'],
            ['inventory.adjusted', 'equipment_inventory', null, 'Adjusted Cebu ball tube count after inventory', 'staff'],
            ['reservation.cancelled', 'reservations', $reservations['PBJ-CEB-3002'], 'Approved customer cancellation and refund', 'admin'],
        ];

        foreach ($audits as [$action, $entityType, $entityId, $summary, $userKey]) {
            DB::table('audit_logs')->updateOrInsert(
                ['action' => $action, 'entity_type' => $entityType, 'entity_id' => $entityId],
                [
                    'user_id' => $users[$userKey]->id,
                    'old_values' => json_encode(['status' => 'pending']),
                    'new_values' => json_encode(['summary' => $summary]),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Pickle Ballan ni Juan dashboard',
                    'request_method' => 'POST',
                    'request_url' => '/modules/'.$entityType,
                    'response_status' => 200,
                    'execution_time_ms' => 84,
                    'created_at' => now()->subHours(2),
                ],
            );
        }

        foreach ($locations as $slug => $locationId) {
            foreach (range(0, 6) as $offset) {
                $date = now()->subDays($offset)->toDateString();
                $locationBump = match ($slug) {
                    'bgc-smash-hub' => 3,
                    'qc-rally-center' => 2,
                    default => 1,
                };
                $reservationsCount = max(3, 8 + $locationBump - ($offset % 3));
                $online = max(1, $reservationsCount - 2);
                $walkin = $reservationsCount - $online;
                $revenue = ($reservationsCount * (650 + ($locationBump * 50))) + (120 * $locationBump);

                DB::table('daily_reports_aggregates')->updateOrInsert(
                    ['report_date' => $date, 'location_id' => $locationId],
                    [
                        'total_reservations' => $reservationsCount,
                        'total_online_reservations' => $online,
                        'total_walkin_reservations' => $walkin,
                        'total_cancellations' => $offset === 1 && $slug === 'cebu-paddle-yard' ? 1 : 0,
                        'total_no_shows' => $offset === 5 && $slug === 'qc-rally-center' ? 1 : 0,
                        'total_revenue' => $revenue,
                        'total_gcash_revenue' => (int) ($revenue * 0.72),
                        'total_cash_revenue' => (int) ($revenue * 0.28),
                        'total_equipment_rented_rackets' => 4 + $locationBump,
                        'total_equipment_rented_balls' => 3 + $locationBump,
                        'average_rating' => 4.4 + ($locationBump / 10),
                        'peak_hour_start' => '18:00:00',
                        'peak_hour_end' => '20:00:00',
                        'utilization_rate' => min(96, 58 + ($locationBump * 7) - $offset),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }

    /**
     * Keep the demo aligned with the real venue setup: one geotagged location and four side-by-side courts.
     *
     * @param  array<string, User>  $users
     */
    private function seedSingleVenueWithFourCourts(array $users): void
    {
        $slug = 'pickle-ballan-ni-juan';
        $hours = json_encode([
            'monday' => ['open' => '06:00', 'close' => '22:00'],
            'tuesday' => ['open' => '06:00', 'close' => '22:00'],
            'wednesday' => ['open' => '06:00', 'close' => '22:00'],
            'thursday' => ['open' => '06:00', 'close' => '22:00'],
            'friday' => ['open' => '06:00', 'close' => '23:00'],
            'saturday' => ['open' => '07:00', 'close' => '23:00'],
            'sunday' => ['open' => '07:00', 'close' => '21:00'],
        ]);

        DB::table('locations')->updateOrInsert(
            ['slug' => $slug],
            [
                'name' => 'Pickle Ballan ni Juan',
                'branch_code' => 'PBJ01',
                'address_line1' => 'Pickle Ballan ni Juan Court Compound',
                'address_line2' => 'Main outdoor court yard',
                'city' => 'Digos City',
                'province' => 'Davao del Sur',
                'postal_code' => '8002',
                'country' => 'Philippines',
                'latitude' => 6.77025,
                'longitude' => 125.2115287,
                'google_maps_embed_url' => 'https://maps.google.com/?q=6.77025,125.2115287',
                'whatsapp_number' => '09123456789',
                'landline_number' => null,
                'email_address' => 'hello@pickleballannijuan.test',
                'operating_hours' => $hours,
                'timezone' => 'Asia/Manila',
                'manager_id' => $users['manager']->id,
                'is_active' => true,
                'opening_date' => now()->subMonths(8)->toDateString(),
                'featured_image_path' => 'images/671478450_122128991829155269_859094970721700938_n.jpg',
                'gallery_images' => json_encode([
                    'images/671478450_122128991829155269_859094970721700938_n.jpg',
                    'soft-ui-dashboard-main/assets/img/home-decor-1.jpg',
                ]),
                'created_by' => $users['admin']->id,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        );

        $locationId = (int) DB::table('locations')->where('slug', $slug)->value('id');
        $oldLocationIds = DB::table('locations')->where('slug', '<>', $slug)->pluck('id');

        if ($oldLocationIds->isNotEmpty()) {
            DB::table('locations')
                ->whereIn('id', $oldLocationIds)
                ->update(['is_active' => false, 'deleted_at' => now(), 'updated_at' => now()]);

            DB::table('courts')
                ->whereIn('location_id', $oldLocationIds)
                ->update(['is_active' => false, 'deleted_at' => now(), 'updated_at' => now()]);
        }

        DB::table('staff_profiles')
            ->whereIn('user_id', [$users['manager']->id, $users['staff']->id, $users['frontdesk']->id])
            ->update(['assigned_location_id' => $locationId, 'updated_at' => now()]);

        $courts = [
            ['A', 'Court A', 1],
            ['B', 'Court B', 2],
            ['C', 'Court C', 3],
            ['D', 'Court D', 4],
        ];

        foreach ($courts as [$number, $name, $order]) {
            DB::table('courts')->updateOrInsert(
                ['location_id' => $locationId, 'court_number' => $number],
                [
                    'court_name' => $name,
                    'court_type' => 'outdoor',
                    'surface_type' => 'acrylic',
                    'width' => 6.10,
                    'length' => 13.41,
                    'has_lighting' => true,
                    'has_net' => true,
                    'has_seating' => true,
                    'has_shade' => false,
                    'is_airconditioned' => false,
                    'is_active' => true,
                    'display_order' => $order,
                    'description' => $name.' is one of four adjacent outdoor pickleball courts at the venue.',
                    'special_instructions' => 'Use non-marking shoes and check in 15 minutes before your schedule.',
                    'created_by' => $users['admin']->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );

            $courtId = (int) DB::table('courts')
                ->where('location_id', $locationId)
                ->where('court_number', $number)
                ->value('id');

            DB::table('court_images')->updateOrInsert(
                ['court_id' => $courtId, 'image_path' => 'images/671478450_122128991829155269_859094970721700938_n.jpg'],
                [
                    'image_filename' => 'pickle-ballan-court-'.$number.'.jpg',
                    'image_size' => 245000,
                    'mime_type' => 'image/jpeg',
                    'alt_text' => 'Pickle Ballan ni Juan '.$name,
                    'caption' => $name.' beside the other venue courts.',
                    'is_primary' => true,
                    'display_order' => 1,
                    'uploaded_by' => $users['admin']->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );

            foreach (range(0, 6) as $day) {
                DB::table('court_schedules')->updateOrInsert(
                    ['court_id' => $courtId, 'day_of_week' => $day],
                    [
                        'open_time' => in_array($day, [0, 6], true) ? '07:00:00' : '06:00:00',
                        'close_time' => in_array($day, [5, 6], true) ? '23:00:00' : '22:00:00',
                        'break_start_time' => '12:00:00',
                        'break_end_time' => '12:30:00',
                        'is_available' => true,
                        'effective_from' => now()->subMonths(6)->toDateString(),
                        'effective_to' => null,
                        'created_by' => $users['admin']->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }

            DB::table('court_pricing_rules')->updateOrInsert(
                ['court_id' => $courtId, 'rule_name' => 'Standard Rate'],
                [
                    'day_of_week' => null,
                    'start_time' => '06:00:00',
                    'end_time' => '23:00:00',
                    'is_holiday' => false,
                    'is_peak_season' => false,
                    'base_price' => 600,
                    'peak_surcharge_percentage' => 0,
                    'minimum_hours' => 1,
                    'maximum_hours' => 4,
                    'priority' => 1,
                    'is_active' => true,
                    'effective_from' => now()->subMonths(6)->toDateString(),
                    'effective_to' => null,
                    'created_by' => $users['admin']->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $equipment = [
            'premium-paddle' => ['Pickleball Paddle Rental', 'Paddles available for court bookings.', 120, 300, 20, 20, 5],
            'training-ball-tube' => ['Pickleball Ball Tube', 'Practice balls available per booking.', 60, 0, 30, 30, 8],
        ];

        foreach ($equipment as $equipmentSlug => [$name, $description, $price, $deposit, $total, $available, $reorder]) {
            DB::table('equipment_types')->updateOrInsert(
                ['slug' => $equipmentSlug],
                [
                    'category_id' => null,
                    'name' => $name,
                    'description' => $description,
                    'rental_price_per_unit' => $price,
                    'deposit_amount' => $deposit,
                    'late_fee_per_hour' => 20,
                    'damage_replacement_cost' => $equipmentSlug === 'premium-paddle' ? 2500 : 600,
                    'is_available_for_rent' => true,
                    'requires_deposit' => $deposit > 0,
                    'max_rental_quantity_per_booking' => $equipmentSlug === 'premium-paddle' ? 4 : 6,
                    'display_order' => $equipmentSlug === 'premium-paddle' ? 1 : 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            );

            $equipmentTypeId = (int) DB::table('equipment_types')->where('slug', $equipmentSlug)->value('id');

            DB::table('equipment_inventory')->updateOrInsert(
                ['location_id' => $locationId, 'equipment_type_id' => $equipmentTypeId],
                [
                    'total_quantity' => $total,
                    'available_quantity' => $available,
                    'reserved_quantity' => 0,
                    'damaged_quantity' => 0,
                    'lost_quantity' => 0,
                    'under_maintenance_quantity' => 0,
                    'last_inventory_count_at' => now(),
                    'last_inventory_count_by' => $users['staff']->id,
                    'reorder_point' => $reorder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('equipment_types')
            ->whereNotIn('slug', array_keys($equipment))
            ->update(['is_available_for_rent' => false, 'updated_at' => now()]);

        DB::table('daily_reports_aggregates')->updateOrInsert(
            ['report_date' => now()->toDateString(), 'location_id' => $locationId],
            [
                'total_reservations' => DB::table('reservations')->where('location_id', $locationId)->where('reservation_date', now()->toDateString())->count(),
                'total_online_reservations' => DB::table('reservations')->where('location_id', $locationId)->where('reservation_date', now()->toDateString())->where('reservation_type', 'online')->count(),
                'total_walkin_reservations' => DB::table('reservations')->where('location_id', $locationId)->where('reservation_date', now()->toDateString())->where('reservation_type', 'walk_in')->count(),
                'total_cancellations' => 0,
                'total_no_shows' => 0,
                'total_revenue' => DB::table('payments as p')->join('reservations as r', 'r.id', '=', 'p.reservation_id')->where('r.location_id', $locationId)->whereDate('p.created_at', now()->toDateString())->where('p.status', 'verified')->sum('p.amount'),
                'total_gcash_revenue' => DB::table('payments as p')->join('reservations as r', 'r.id', '=', 'p.reservation_id')->where('r.location_id', $locationId)->whereDate('p.created_at', now()->toDateString())->where('p.status', 'verified')->where('p.payment_method', 'gcash')->sum('p.amount'),
                'total_cash_revenue' => DB::table('payments as p')->join('reservations as r', 'r.id', '=', 'p.reservation_id')->where('r.location_id', $locationId)->whereDate('p.created_at', now()->toDateString())->where('p.status', 'verified')->where('p.payment_method', 'cash')->sum('p.amount'),
                'total_equipment_rented_rackets' => 0,
                'total_equipment_rented_balls' => 0,
                'average_rating' => 4.8,
                'peak_hour_start' => '18:00:00',
                'peak_hour_end' => '20:00:00',
                'utilization_rate' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function hoursBetween(string $date, string $start, string $end): float
    {
        $startAt = Carbon::parse($date.' '.$start);
        $endAt = Carbon::parse($date.' '.$end);

        return round($startAt->diffInMinutes($endAt) / 60, 2);
    }

    private function todayAt(string $time): CarbonInterface
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return now()->setTime($hour, $minute);
    }
}
