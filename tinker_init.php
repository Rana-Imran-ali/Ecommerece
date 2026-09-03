<?php

use App\Models\User;

try {
    $user = User::first() ?? User::factory()->create();
} catch (\Throwable $e) {
    // Silently ignore if DB is not reachable
}
