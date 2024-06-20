<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Password) {
            throw new UnexpectedTypeException($constraint, Password::class);
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (
            (!empty($value)) && (trim($value) === $value) && !\str_contains($value, ' ') &&
            preg_match('/^(?=.*?[A-Z])(?=.*?[#?!@$%^&*-]).{8,}$/', $value, $matches)
        ) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
