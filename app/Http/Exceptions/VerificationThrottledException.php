<?php

namespace App\Http\Exceptions;

use RuntimeException;

/**
 * Raised when a user exceeds the per-day verification attempt limit.
 * Renders as HTTP 429 so the PWA can pause and prompt for tomorrow.
 */
class VerificationThrottledException extends RuntimeException {}
