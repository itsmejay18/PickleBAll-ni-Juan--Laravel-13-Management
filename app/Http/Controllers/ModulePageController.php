<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModulePageController extends Controller
{
    public function __invoke(Request $request, string $module): View
    {
        $page = $this->pages()[$module] ?? throw new NotFoundHttpException();

        return view('modules.show', [
            'module' => $module,
            'page' => $page,
        ]);
    }

    /**
     * @return array<string, array{
     *     title: string,
     *     eyebrow: string,
     *     description: string,
     *     icon: string,
     *     objectives: string,
     *     owner: string,
     *     status: string,
     *     cards: array<int, array{title: string, text: string, icon: string}>,
     *     rows: array<int, array{feature: string, objective: string, note: string}>
     * }>
     */
    private function pages(): array
    {
        return [
            'locations' => [
                'title' => 'Location Management',
                'eyebrow' => 'Admin Module',
                'description' => 'Manage branches, operating hours, addresses, contact details, and map-ready coordinates.',
                'icon' => 'fa-map-marker-alt',
                'objectives' => 'B1, B4, L3',
                'owner' => 'Super Admin, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Branches', 'text' => 'Create and update location records for every Pickle Ball ni Juan branch.', 'icon' => 'fa-building'],
                    ['title' => 'Operating Hours', 'text' => 'Prepare independent schedules per location and court.', 'icon' => 'fa-clock'],
                    ['title' => 'Map Details', 'text' => 'Store addresses and coordinates for directions.', 'icon' => 'fa-map'],
                ],
                'rows' => [
                    ['feature' => 'Branch directory', 'objective' => 'B1', 'note' => 'Multiple locations with independent settings.'],
                    ['feature' => 'Google Maps details', 'objective' => 'B4', 'note' => 'Pins, addresses, and direction-ready data.'],
                    ['feature' => 'Admin location setup', 'objective' => 'L3', 'note' => 'Create, edit, and manage branch profile data.'],
                ],
            ],
            'courts' => [
                'title' => 'Court Management',
                'eyebrow' => 'Admin Module',
                'description' => 'Set up courts, photos, schedules, maintenance closures, and pricing-ready court records.',
                'icon' => 'fa-table-tennis',
                'objectives' => 'B2-B7, C1-C6',
                'owner' => 'Super Admin, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Court Records', 'text' => 'Add courts per branch with status and basic metadata.', 'icon' => 'fa-table-tennis'],
                    ['title' => 'Photos', 'text' => 'Plan multiple court photos with primary image support.', 'icon' => 'fa-images'],
                    ['title' => 'Maintenance', 'text' => 'Block regular, recurring, event, or emergency closures.', 'icon' => 'fa-tools'],
                ],
                'rows' => [
                    ['feature' => 'Court CRUD', 'objective' => 'B2', 'note' => 'Add, edit, delete, and manage courts.'],
                    ['feature' => 'Court photos', 'objective' => 'B3', 'note' => 'Multiple images and primary selection.'],
                    ['feature' => 'Maintenance closures', 'objective' => 'B6-B7', 'note' => 'Single and recurring closures.'],
                    ['feature' => 'Pricing rules', 'objective' => 'C1-C6', 'note' => 'Rates, surcharges, taxes, limits, and discounts.'],
                ],
            ],
            'payments' => [
                'title' => 'Payment Verification',
                'eyebrow' => 'Admin Module',
                'description' => 'Review GCash screenshots, reference numbers, approvals, rejections, and verification audit details.',
                'icon' => 'fa-money-check-alt',
                'objectives' => 'F1-F12, G1-G7',
                'owner' => 'Admin, Staff with permission',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Pending Proof', 'text' => 'List uploaded screenshots and reference numbers.', 'icon' => 'fa-receipt'],
                    ['title' => 'Confirm or Reject', 'text' => 'Move reservations to paid or cancelled with a reason.', 'icon' => 'fa-check-circle'],
                    ['title' => 'Digital Receipt', 'text' => 'Generate receipt after payment confirmation.', 'icon' => 'fa-file-invoice'],
                ],
                'rows' => [
                    ['feature' => 'GCash proof upload', 'objective' => 'F1-F5', 'note' => 'Customer submits screenshot and reference number.'],
                    ['feature' => 'Staff verification', 'objective' => 'F6-F10', 'note' => 'Confirm, reject, or process walk-in cash.'],
                    ['feature' => 'Audit trail', 'objective' => 'F11', 'note' => 'Track who verified each payment and when.'],
                    ['feature' => 'Receipt access', 'objective' => 'G1-G7', 'note' => 'Receipt available in dashboard and check-in screen.'],
                ],
            ],
            'equipment' => [
                'title' => 'Equipment Inventory',
                'eyebrow' => 'Admin Module',
                'description' => 'Track rackets, balls, rentals, stock status, low-stock alerts, damage logs, and deposits.',
                'icon' => 'fa-boxes',
                'objectives' => 'D1-D9, O6-O7',
                'owner' => 'Admin, Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Inventory States', 'text' => 'Available, reserved, damaged, lost, and under maintenance.', 'icon' => 'fa-warehouse'],
                    ['title' => 'Rental Pricing', 'text' => 'Set prices per equipment type.', 'icon' => 'fa-tags'],
                    ['title' => 'Damage Charges', 'text' => 'Log return status and add customer charges.', 'icon' => 'fa-exclamation-triangle'],
                ],
                'rows' => [
                    ['feature' => 'Equipment selection', 'objective' => 'D1-D2', 'note' => 'Customers rent gear during booking.'],
                    ['feature' => 'Inventory deduction', 'objective' => 'D3-D5', 'note' => 'Deduct on confirmation, restore on cancellation.'],
                    ['feature' => 'Low-stock and damage', 'objective' => 'D6-D9', 'note' => 'Alerts, loss, damage, charges, and deposits.'],
                    ['feature' => 'Inventory reports', 'objective' => 'O6-O7', 'note' => 'Usage and stock-level reporting.'],
                ],
            ],
            'reports' => [
                'title' => 'Reports and Audit',
                'eyebrow' => 'Admin Module',
                'description' => 'Monitor revenue, utilization, peak hours, cancellations, no-shows, customers, exports, and audit logs.',
                'icon' => 'fa-file-export',
                'objectives' => 'L9-L11, O1-O10, P2',
                'owner' => 'Super Admin, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Revenue', 'text' => 'Daily, weekly, and monthly income by location and payment method.', 'icon' => 'fa-coins'],
                    ['title' => 'Utilization', 'text' => 'Booked court hours compared with available court hours.', 'icon' => 'fa-chart-line'],
                    ['title' => 'Audit Logs', 'text' => 'Significant system actions with user and timestamp.', 'icon' => 'fa-history'],
                ],
                'rows' => [
                    ['feature' => 'Revenue and bookings', 'objective' => 'O1-O2', 'note' => 'Revenue and reservation volume reporting.'],
                    ['feature' => 'Operations reports', 'objective' => 'O3-O8', 'note' => 'Peak hours, cancellations, no-shows, inventory, utilization.'],
                    ['feature' => 'Customer and export reports', 'objective' => 'O9-O10', 'note' => 'Customer behavior and export functions.'],
                    ['feature' => 'Audit trail', 'objective' => 'P2', 'note' => 'Log significant actions across the system.'],
                ],
            ],
            'walk-ins' => [
                'title' => 'Walk-in Booking',
                'eyebrow' => 'Staff Module',
                'description' => 'Create counter bookings for walk-in customers with cash or manually verified GCash payment.',
                'icon' => 'fa-walking',
                'objectives' => 'H1-H7',
                'owner' => 'Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Counter Booking', 'text' => 'Create reservations for customers at the branch.', 'icon' => 'fa-calendar-plus'],
                    ['title' => 'Cash or GCash', 'text' => 'Mark cash as paid or verify GCash manually.', 'icon' => 'fa-cash-register'],
                    ['title' => 'Receipt', 'text' => 'Generate a digital receipt for the walk-in customer.', 'icon' => 'fa-receipt'],
                ],
                'rows' => [
                    ['feature' => 'Staff-created reservation', 'objective' => 'H1', 'note' => 'Staff books on behalf of walk-in customers.'],
                    ['feature' => 'Payment handling', 'objective' => 'H2-H3', 'note' => 'Cash or manual GCash verification.'],
                    ['feature' => 'Customer details', 'objective' => 'H4-H5', 'note' => 'Name, mobile number, optional account creation.'],
                    ['feature' => 'Receipt and inventory', 'objective' => 'H6-H7', 'note' => 'Receipt generation and equipment deduction.'],
                ],
            ],
            'check-ins' => [
                'title' => 'Check-in',
                'eyebrow' => 'Staff Module',
                'description' => 'Search reservations, validate payment status, mark arrivals, and release rented equipment.',
                'icon' => 'fa-clipboard-check',
                'objectives' => 'I1-I5',
                'owner' => 'Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Search', 'text' => 'Find reservations by ID, name, date, time, or court.', 'icon' => 'fa-search'],
                    ['title' => 'Validate', 'text' => 'Review full reservation and payment details.', 'icon' => 'fa-id-card'],
                    ['title' => 'Release Gear', 'text' => 'Mark equipment as released during arrival.', 'icon' => 'fa-hand-holding'],
                ],
                'rows' => [
                    ['feature' => 'Reservation search', 'objective' => 'I1', 'note' => 'Search by ID, customer, or schedule details.'],
                    ['feature' => 'Reservation details', 'objective' => 'I2', 'note' => 'Show payment status and equipment rented.'],
                    ['feature' => 'Arrival record', 'objective' => 'I3-I4', 'note' => 'Record check-in time and staff user.'],
                    ['feature' => 'Equipment release', 'objective' => 'I5', 'note' => 'Release rented items to customer.'],
                ],
            ],
            'check-outs' => [
                'title' => 'Check-out',
                'eyebrow' => 'Staff Module',
                'description' => 'Complete sessions, record equipment returns, damage or loss, and collect additional charges.',
                'icon' => 'fa-sign-out-alt',
                'objectives' => 'I6-I9',
                'owner' => 'Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Complete Session', 'text' => 'Mark reservation as completed after play time.', 'icon' => 'fa-check-double'],
                    ['title' => 'Return Status', 'text' => 'Record returned, damaged, or lost equipment.', 'icon' => 'fa-clipboard-list'],
                    ['title' => 'Extra Charges', 'text' => 'Calculate and collect late or damage fees.', 'icon' => 'fa-coins'],
                ],
                'rows' => [
                    ['feature' => 'Session completion', 'objective' => 'I6', 'note' => 'Complete the reservation after the session ends.'],
                    ['feature' => 'Equipment return', 'objective' => 'I7', 'note' => 'Track returned, damaged, or lost equipment.'],
                    ['feature' => 'Additional charges', 'objective' => 'I8-I9', 'note' => 'Damage and late fees with payment collection.'],
                ],
            ],
            'book-court' => [
                'title' => 'Book Court',
                'eyebrow' => 'Customer Module',
                'description' => 'Browse court availability, choose a slot, add equipment, review pricing, and submit booking.',
                'icon' => 'fa-calendar-plus',
                'objectives' => 'N3-N5, E1-E10',
                'owner' => 'End User',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Availability', 'text' => 'Browse by date, branch, court, and time.', 'icon' => 'fa-calendar-day'],
                    ['title' => 'Equipment', 'text' => 'Add rackets or balls to the reservation.', 'icon' => 'fa-table-tennis'],
                    ['title' => 'Pricing', 'text' => 'Review court fee, rentals, discounts, taxes, and total.', 'icon' => 'fa-calculator'],
                ],
                'rows' => [
                    ['feature' => 'Calendar browsing', 'objective' => 'E1-E7, N3', 'note' => 'Find available courts and equipment.'],
                    ['feature' => 'Reservation creation', 'objective' => 'E8-E10, N4', 'note' => 'Calculate price and hold pending slots.'],
                    ['feature' => 'Payment handoff', 'objective' => 'N5', 'note' => 'Proceed to GCash payment proof upload.'],
                ],
            ],
            'receipts' => [
                'title' => 'Receipts',
                'eyebrow' => 'Customer Module',
                'description' => 'View confirmed digital receipts for court reservations, equipment rentals, and payment details.',
                'icon' => 'fa-receipt',
                'objectives' => 'G1-G7, N6',
                'owner' => 'End User, Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Receipt Details', 'text' => 'Reservation ID, customer, court, date, time, and amounts.', 'icon' => 'fa-file-invoice'],
                    ['title' => 'GCash Reference', 'text' => 'Show payment method and reference number.', 'icon' => 'fa-wallet'],
                    ['title' => 'Access Anytime', 'text' => 'Receipts remain available in the user dashboard.', 'icon' => 'fa-clock'],
                ],
                'rows' => [
                    ['feature' => 'Digital receipt generation', 'objective' => 'G1-G2', 'note' => 'Receipt created after payment confirmation.'],
                    ['feature' => 'Dashboard access', 'objective' => 'G3, N6', 'note' => 'Users can view receipts anytime.'],
                    ['feature' => 'Email and staff resend', 'objective' => 'G4-G7', 'note' => 'Email, screenshot, resend, and check-in display.'],
                ],
            ],
            'reviews' => [
                'title' => 'Reviews',
                'eyebrow' => 'Customer Module',
                'description' => 'Collect customer ratings, court reviews, admin responses, moderation, and audited rating changes.',
                'icon' => 'fa-star',
                'objectives' => 'K1-K10, N8',
                'owner' => 'End User, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Ratings', 'text' => 'Rate completed bookings from 1 to 5 stars.', 'icon' => 'fa-star-half-alt'],
                    ['title' => 'Review Text', 'text' => 'Leave comments and optional photos.', 'icon' => 'fa-comment-alt'],
                    ['title' => 'Moderation', 'text' => 'Admin responds, hides, adjusts, and audits changes.', 'icon' => 'fa-user-shield'],
                ],
                'rows' => [
                    ['feature' => 'Customer ratings', 'objective' => 'K1-K4, N8', 'note' => 'Rating and review flow after completed bookings.'],
                    ['feature' => 'Admin management', 'objective' => 'K5-K8', 'note' => 'View, respond, adjust, or hide reviews.'],
                    ['feature' => 'Helpfulness and audit', 'objective' => 'K9-K10', 'note' => 'Helpful votes and audited rating changes.'],
                ],
            ],
        ];
    }
}
