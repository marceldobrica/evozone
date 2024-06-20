<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Programme;
use App\Repository\ProgrammeRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ProgrammeRoomOccupiedValidator extends ConstraintValidator
{
    private ProgrammeRepository $programmeRepository;

    public function __construct(ProgrammeRepository $programmeRepository)
    {
        $this->programmeRepository = $programmeRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Programme) {
            throw new UnexpectedTypeException($value, Programme::class);
        }

        if (!$constraint instanceof ProgrammeRoomOccupied) {
            throw new UnexpectedTypeException($constraint, ProgrammeRoomOccupied::class);
        }

        $result = $this->programmeRepository->isRoomOccupied(
            $value->getStartDate(),
            $value->getEndDate(),
            $value->getRoom()->getId()
        );

        if (empty($result)) {
            return;
        }

        if (count($result) == 1 && $value->getId() === $result[0]['programmeid']) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
