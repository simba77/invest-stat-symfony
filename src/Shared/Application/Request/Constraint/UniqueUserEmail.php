<?php

declare(strict_types=1);

namespace App\Shared\Application\Request\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * The email is not used by any account other than the current user's.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class UniqueUserEmail extends Constraint
{
    public string $message = 'This email is already used by another account.';
}
