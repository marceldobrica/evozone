<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\DateTimeInFuture;
use App\Validator\DateTimeInFutureValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DateTimeInFutureValidatorTest extends ConstraintValidatorTestCase
{
    public function setUp(): void
    {
        $this->constraint = $this->createMock(Constraint::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new DateTimeInFutureValidator();
    }

    public function provideDateTime(): array
    {
        return [
            [new \DateTime('now'), false],
            [new \DateTime('-1 day'), false],
            [new \DateTime('+1 day'), true],
        ];
    }

    /**
     * @dataProvider provideDateTime
     */
    public function testDatesInFuture(\DateTime $dateTime, bool $expectedValid): void
    {
        $this->validator->validate($dateTime, new DateTimeInFuture());
        if ($expectedValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('Programmes start and end dates should be greater than current moment! ' .
                'You are not allowed to modify past programmes.')->assertRaised();
        }
    }

    public function testUnexpectedTypeExceptionValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('someText', new DateTimeInFuture());
    }

    public function testUnexpectedTypeExceptionConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \DateTime('-1 day'), $this->constraint);
    }
}
