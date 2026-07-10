<?php

declare(strict_types=1);

namespace Odaf\Engine\Workflow\Contracts;

use RuntimeException;

/**
 * Dilempar ketika operasi workflow tidak valid atau tidak diizinkan.
 */
final class WorkflowException extends RuntimeException {}
