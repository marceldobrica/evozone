<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Programme;
use App\Repository\ProgrammeRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ProgrammeTrainerAvailableValidator extends ConstraintValidator
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

        if (!$constraint instanceof ProgrammeTrainerAvailable) {
            throw new UnexpectedTypeException($constraint, ProgrammeTrainerAvailable::class);
        }

        if (is_null($value->getTrainer())) {
            return;
        }

        $resultTrainer = $this->programmeRepository->isUserOcupiedAsTrainer(
            $value->getStartDate(),
            $value->getEndDate(),
            $value->getTrainer()->getId()
        );

        $resultCustomer = $this->programmeRepository->isUserOcupiedAsCustomer(
            $value->getStartDate(),
            $value->getEndDate(),
            $value->getTrainer()->getId()
        );

        if (
            (count($resultTrainer) == 1 && $value->getId() === $resultTrainer[0]['programmeid']) ||
            empty($resultTrainer) &&
            empty($resultCustomer)
        ) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
