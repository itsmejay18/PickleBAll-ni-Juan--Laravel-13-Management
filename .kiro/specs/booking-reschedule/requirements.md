# Requirements Document

## Introduction

This feature adds a complete booking reschedule capability to the Pickleball court management system. Administrators can globally enable or disable rescheduling, and can lock/unlock rescheduling on individual reservations. Clients can reschedule their confirmed bookings when permitted, with conflict detection preventing double bookings. The "Book a Court" calendar displays unavailable and booked time slots so users can select only open windows. All original booking data (payment records, equipment, audit trail) is preserved through the reschedule process.

## Glossary

- **Reschedule_System**: The subsystem responsible for managing the rescheduling of existing reservations, including global toggle, per-reservation lock, conflict detection, and schedule updates.
- **Admin**: A user with the super_admin or admin role who can manage system-wide settings and reservation locks.
- **Staff**: A user with the staff or location_manager role who can manage reservations at their assigned location.
- **Client**: A user with the end_user role who owns a reservation.
- **Global_Reschedule_Setting**: A system-level boolean setting (stored in SystemSetting) that controls whether rescheduling is available to Clients across the entire platform.
- **Reschedule_Lock**: A per-reservation flag that prevents a specific reservation from being rescheduled by the Client, regardless of the Global_Reschedule_Setting.
- **Calendar_View**: The "Book a Court" interface that displays court availability with time slots marked as available, booked, or under maintenance.
- **Time_Slot**: A discrete block of time on a specific court and date, defined by a start_time and end_time.
- **Conflict**: A condition where a requested Time_Slot overlaps with an existing active reservation (status not in cancelled, no_show, or refunded) on the same court and date.
- **Reschedule_History**: A log entry recording the original schedule and new schedule each time a reservation is rescheduled.
- **Pending_Reschedule**: A reservation status indicating that an Admin has initiated a reschedule and the Client needs to select a new time slot.

## Requirements

### Requirement 1: Global Reschedule Toggle

**User Story:** As an Admin, I want to enable or disable rescheduling system-wide, so that I can control whether Clients are allowed to reschedule their bookings.

#### Acceptance Criteria

1. THE Reschedule_System SHALL store the Global_Reschedule_Setting as a boolean value in the SystemSetting table with the key `reschedule_enabled`.
2. WHEN an Admin updates the Global_Reschedule_Setting, THE Reschedule_System SHALL persist the new value and clear the cached setting within 1 second of the update request completing.
3. WHILE the Global_Reschedule_Setting is false, THE Reschedule_System SHALL reject all Client-initiated reschedule requests with a message indicating rescheduling is currently disabled.
4. WHILE the Global_Reschedule_Setting is true, THE Reschedule_System SHALL allow Client-initiated reschedule requests (subject to per-reservation Reschedule_Lock checks).
5. IF no setting record with the key `reschedule_enabled` exists in the SystemSetting table, THEN THE Reschedule_System SHALL treat the Global_Reschedule_Setting as false.
6. IF a non-Admin user attempts to update the Global_Reschedule_Setting, THEN THE Reschedule_System SHALL reject the request with a message indicating insufficient permissions and leave the existing setting unchanged.
7. IF the persistence of the Global_Reschedule_Setting update fails, THEN THE Reschedule_System SHALL return an error message indicating the setting was not saved and retain the previous setting value.

### Requirement 2: Per-Reservation Reschedule Lock

**User Story:** As an Admin or Staff member, I want to lock or unlock rescheduling on individual reservations with a single click, so that I can quickly control which bookings can be rescheduled.

#### Acceptance Criteria

1. WHEN an Admin or Staff member clicks the Lock button on a reservation, THE Reschedule_System SHALL set the reschedule_locked flag to true in a single click without requiring a reason or comment.
2. WHEN an Admin or Staff member clicks the Unlock button on a reservation, THE Reschedule_System SHALL set the reschedule_locked flag to false in a single click without requiring a reason or comment.
3. WHILE a reservation has reschedule_locked set to true, THE Reschedule_System SHALL reject Client-initiated reschedule selections for that reservation.
4. THE Reschedule_System SHALL allow Admin and Staff to reschedule a reservation regardless of the reschedule_locked status.
5. THE Reschedule_System SHALL default the reschedule_locked flag to true for newly created reservations.
6. IF an Admin or Staff member locks a reservation that is already locked or unlocks a reservation that is already unlocked, THEN THE Reschedule_System SHALL process the request idempotently and return a success response without error.

### Requirement 3: Admin Bulk Reschedule by Time Range

**User Story:** As an Admin or Staff member, I want to select a date and time range (e.g., 1 PM to 5 PM on a specific day) and see all bookings in that window, so that I can select all or specific bookings to initiate a reschedule (e.g., due to rain or maintenance).

#### Acceptance Criteria

1. THE Reschedule_System SHALL provide an interface where Admin or Staff can select a specific date and a time range (start_time and end_time) to filter active reservations.
2. WHEN an Admin or Staff member selects a date and time range, THE Reschedule_System SHALL display all active reservations (status confirmed or payment_verification) that overlap with the selected time range on that date, across all courts at the location.
3. THE Reschedule_System SHALL provide a "Check All" checkbox that selects all displayed reservations in the filtered list.
4. THE Reschedule_System SHALL allow Admin or Staff to individually select or deselect specific reservations from the filtered list using checkboxes.
5. WHEN an Admin or Staff member clicks the Reschedule button with one or more reservations selected, THE Reschedule_System SHALL set all selected reservations to `pending_reschedule` status in a single action.
6. WHEN reservations are set to `pending_reschedule`, THE Reschedule_System SHALL send a notification to each affected reservation owner informing them that their booking needs to be rescheduled, including the reservation_code, court name, and original date/time.
7. THE notification SHALL include a direct link that forwards the Client to the Calendar_View to select a new time slot for that reservation.
8. THE Reschedule_System SHALL only allow bulk reschedule on reservations with status confirmed or payment_verification.

### Requirement 4: Client Reschedule Selection

**User Story:** As a Client, I want to select a new date and time for my rescheduled booking after being notified by the Admin, so that I can continue my reservation at a convenient time.

#### Acceptance Criteria

1. WHEN a Client clicks the reschedule link from the notification, THE Reschedule_System SHALL forward the Client to the Calendar_View pre-filtered to the reservation's court.
2. THE Reschedule_System SHALL only allow a Client to select a new time for reservations owned by that Client (where reservation.user_id matches the authenticated user).
3. THE Reschedule_System SHALL only allow time selection on reservations with status `pending_reschedule`.
4. IF a Client attempts to reschedule a reservation that is not in `pending_reschedule` status, THEN THE Reschedule_System SHALL reject the request with an error message indicating the reservation is not awaiting reschedule.
5. WHEN a Client selects a new time, THE Reschedule_System SHALL require a new reservation_date (format: YYYY-MM-DD), start_time (format: HH:mm), and end_time (format: HH:mm).
6. THE Reschedule_System SHALL reject reschedule selections where the new reservation_date is earlier than the current date.
7. THE Reschedule_System SHALL reject reschedule selections where the new reservation_date is more than 15 days from the current date.
8. THE Reschedule_System SHALL enforce that the new Time_Slot duration (end_time minus start_time) is at minimum 1 hour and at maximum 4 hours.
9. WHEN the Client successfully selects a new time, THE Reschedule_System SHALL update the reservation date/time and set the status back to confirmed.

### Requirement 5: Double Booking Prevention

**User Story:** As a system operator, I want the system to prevent double bookings during rescheduling, so that no two active reservations occupy the same court at the same time.

#### Acceptance Criteria

1. WHEN a reschedule request is submitted, THE Reschedule_System SHALL check for Conflicts on the reservation's court_id, the requested reservation_date, and the requested start_time to end_time window before updating the reservation.
2. THE Reschedule_System SHALL define a Conflict as any existing reservation (excluding the reservation being rescheduled) on the same court_id and reservation_date where the existing start_time is before the requested end_time AND the existing end_time is after the requested start_time, and the existing reservation status is not cancelled, no_show, or refunded.
3. IF a Conflict is detected, THEN THE Reschedule_System SHALL reject the reschedule request with a message indicating the selected time slot is unavailable and SHALL NOT modify the reservation record.
4. THE Reschedule_System SHALL execute the conflict check and reservation update within a single database transaction using row-level locking on the target court and date during conflict detection to prevent race conditions.
5. IF the database transaction fails due to a deadlock or lock timeout, THEN THE Reschedule_System SHALL reject the reschedule request with a message indicating the operation could not be completed and SHALL NOT modify the reservation record.
6. WHEN a reschedule request is submitted, THE Reschedule_System SHALL also reject the request with a message indicating the slot is unavailable IF a court_maintenance record overlaps with the requested court_id, reservation_date, and time window.

### Requirement 6: Calendar Availability Display

**User Story:** As a Client, I want to see which time slots are unavailable or booked on the "Book a Court" calendar, so that I can select an open slot when rescheduling.

#### Acceptance Criteria

1. WHEN a Client views the Calendar_View for a specific court and date, THE Reschedule_System SHALL display all Time_Slots for that court and date in 1-hour increments, marking each slot as available, booked, or under maintenance.
2. THE Calendar_View SHALL mark a Time_Slot as booked when an active reservation (status not in cancelled, no_show, or refunded) exists for that court and date where the reservation start_time is before the slot end_time AND the reservation end_time is after the slot start_time.
3. THE Calendar_View SHALL mark a Time_Slot as under maintenance when a court_maintenance record for that court has a start_datetime before the slot end_time AND end_datetime after the slot start_time on that date, or when the court_maintenance is_all_day flag is true for that date.
4. IF the Client is rescheduling an existing reservation, THEN THE Calendar_View SHALL exclude that reservation's Time_Slot from the unavailable display so the Client can see their own slot as available.
5. WHEN a Client selects a Time_Slot marked as unavailable, THE Calendar_View SHALL prevent submission and display a message indicating the slot is unavailable due to an existing booking or scheduled maintenance.
6. WHEN a Client views the Calendar_View, THE Reschedule_System SHALL retrieve availability data no older than 30 seconds and SHALL allow browsing dates from today up to 15 days in advance.

### Requirement 7: Preserve Existing Booking Data

**User Story:** As a system operator, I want all original booking data to remain intact after a reschedule, so that payment records, equipment rentals, and audit history are not lost.

#### Acceptance Criteria

1. WHEN a reservation is rescheduled, THE Reschedule_System SHALL update only the reservation_date, start_time, and end_time fields on the reservation record.
2. WHEN a reservation is rescheduled, THE Reschedule_System SHALL preserve the payment records, equipment rentals, grand_total, court_price_per_hour, reservation_code, and all other existing fields unchanged.
3. WHEN a reservation is rescheduled, THE Reschedule_System SHALL create a Reschedule_History entry recording the original reservation_date, start_time, end_time, the new reservation_date, start_time, end_time, the user who performed the reschedule, and the server-generated UTC timestamp of when the reschedule occurred.
4. THE Reschedule_System SHALL retain all associated payment, check-in, check-out, equipment, and cancellation_log records linked to the reservation after rescheduling.
5. WHEN a reservation is rescheduled, THE Reschedule_System SHALL log an audit entry with action `reservation.rescheduled` containing the reservation_code, old schedule (reservation_date, start_time, end_time before the change), and new schedule (reservation_date, start_time, end_time after the change).
6. WHEN a reservation is rescheduled, THE Reschedule_System SHALL perform the date/time update, Reschedule_History creation, and audit log entry within a single atomic transaction so that either all three succeed or none are persisted.
7. IF the Reschedule_History entry or audit log entry fails to be created during a reschedule, THEN THE Reschedule_System SHALL roll back the reservation date/time changes and return an error message indicating the reschedule could not be completed.

### Requirement 8: Reschedule Notifications

**User Story:** As a Client, I want to receive a notification when my booking is rescheduled, so that I am aware of the updated schedule.

#### Acceptance Criteria

1. WHEN a reservation is successfully rescheduled, THE Reschedule_System SHALL send a notification to the reservation owner within 30 seconds, including the previous date and time, the new date and time, and the court name.
2. WHEN an Admin or Staff reschedules a Client's reservation, THE Reschedule_System SHALL include the name of the staff member who performed the reschedule in the notification message sent to the reservation owner.
3. THE Reschedule_System SHALL send the notification through the existing NotificationService using the `reservation` channel and the associated Reservation model instance.
4. IF the notification fails to be created in the NotificationLog, THEN THE Reschedule_System SHALL log the failure and SHALL NOT revert the reschedule operation.

### Requirement 9: Reschedule Limits

**User Story:** As a system operator, I want to limit how many times a reservation can be rescheduled, so that the system is not abused with excessive schedule changes.

#### Acceptance Criteria

1. THE Reschedule_System SHALL store a `max_reschedules_per_booking` integer value in the SystemSetting table with a valid range of 1 to 10.
2. WHEN a reschedule request is submitted by a Client, THE Reschedule_System SHALL count the existing Reschedule_History entries for that reservation (including entries created by Admin and Staff reschedules) and compare the count against the max_reschedules_per_booking setting.
3. IF the reservation's Reschedule_History entry count is equal to or greater than the max_reschedules_per_booking value, THEN THE Reschedule_System SHALL reject the request with a message indicating the reschedule limit has been reached and include the current count and maximum allowed.
4. IF no `max_reschedules_per_booking` setting record exists in the SystemSetting table, THEN THE Reschedule_System SHALL use a default value of 2.
5. THE Reschedule_System SHALL allow Admin and Staff to reschedule a reservation regardless of the reschedule count limit.
6. WHEN an Admin or Staff reschedules a reservation that has reached or exceeded the limit, THE Reschedule_System SHALL still create a Reschedule_History entry for that reschedule.
