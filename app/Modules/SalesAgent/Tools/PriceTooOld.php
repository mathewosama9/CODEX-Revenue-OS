<?php

namespace App\Modules\SalesAgent\Tools;

use RuntimeException;

/** The synced price is older than the limit: no price, a person answers (US-E09-01). */
final class PriceTooOld extends RuntimeException {}
