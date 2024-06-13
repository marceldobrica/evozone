<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Entity\Programme;
use App\Validator\ProgrammeDateTimeDifference;
use App\Validator\ProgrammeDateTimeDifferenceValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProgrammeDateTimeDifferenceValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ProgrammeDateTimeDifferenceValidator('360', '15');
    }

    public function provideProgramme(): array
    {
        $programmeSmallDifference = new Programme();
        $programmeSmallDifference->setStartDate(new \DateTime('+5minutes'));
        $programmeSmallDifference->setEndDate(new \DateTime('+15minutes'));

        $programmeGreatDifference = new Programme();
        $programmeGreatDifference->setStartDate(new \DateTime('+5minutes'));
        $programmeGreatDifference->setEndDate(new \DateTime('+370minutes'));

        $programmeNormal = new Programme();
        $programmeNormal->setStartDate(new \DateTime('+5minutes'));
        $programmeNormal->setEndDate(new \DateTime('+75minutes'));

        return [
            [$programmeSmallDifference, false],
            [$programmeGreatDifference, false],
            [$programmeNormal, true],
        ];
    }

    /**
     * @dataProvider provideProgramme
     */
    public function testDateDifferences(Programme $programme, bool $expectedValid): void
    {
        $this->validator->validate($programme, new ProgrammeDateTimeDifference());
        if ($expectedValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('Difference between programmes end date and start date should be greater or equal ' .
                'with {{ minTimeMin }} minutes and lesser or equal with {{ maxTimeMin }} minutes')
                ->setParameters([
                '{{ minTimeMin }}' => '15',
                '{{ maxTimeMin }}' => '360'
            ])->assertRaised();
        }
    }

    public function testUnexpectedTypeExceptionValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('someText', new ProgrammeDateTimeDifference());
    }

    public function testUnexpectedTypeExceptionConstraint(): void
    {
        $programmeNormal = new Programme();
        $programmeNormal->setStartDate(new \DateTime('+5minutes'));
        $programmeNormal->setEndDate(new \DateTime('+75minutes'));

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate($programmeNormal, $this->constraint);
    }
}
