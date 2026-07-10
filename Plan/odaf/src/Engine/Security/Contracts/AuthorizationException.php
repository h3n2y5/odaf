<?php

declare(strict_types=1);

namespace Odaf\Engine\Security\Contracts;

use RuntimeException;

/**
 * Dilempar ketika operasi ditolak oleh Security Engine.
 */
final class AuthorizationException extends RuntimeException {}
