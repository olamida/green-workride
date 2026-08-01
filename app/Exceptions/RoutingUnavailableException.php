<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when no routing provider can fulfil a request — OSRM is down and
 * paid fallbacks are disabled or over the monthly budget.
 */
class RoutingUnavailableException extends RuntimeException {}
