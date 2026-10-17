<?php

return [
    // Experimental starting values, NOT a proven capacity for Hostinger.
    // Publish for the pre-Laravel gate with php artisan lentera:admission.
    'enabled' => (bool) env('LENTERA_ADMISSION_ENABLED', false),
    'capacity' => (int) env('LENTERA_ADMISSION_CAPACITY', 8),
    'starts_per_second' => (int) env('LENTERA_ADMISSION_RATE', 8),
    'max_waiting' => (int) env('LENTERA_ADMISSION_MAX_WAITING', 2000),
    'poll_seconds' => (int) env('LENTERA_ADMISSION_POLL_SECONDS', 20),
    'ticket_ttl' => 180,
    'reservation_ttl' => 30,
];
