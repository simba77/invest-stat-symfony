<?php

declare(strict_types=1);

namespace App\Shared\Application\Request\Constraint;

use App\Shared\Domain\User;
use App\Shared\Domain\UserRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @psalm-api Found by the validator through UniqueUserEmail::validatedBy()
 */
final class UniqueUserEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly Security $security,
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof UniqueUserEmail) {
            throw new UnexpectedTypeException($constraint, UniqueUserEmail::class);
        }

        if (! is_string($value) || $value === '') {
            return;
        }

        $owner = $this->userRepository->findByEmail($value);
        if ($owner === null) {
            return;
        }

        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $owner->getId()) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
