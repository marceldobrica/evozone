<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\DateTimeInFuture;
use App\Validator\Password;
use App\Validator\PasswordValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PasswordValidatorTest extends ConstraintValidatorTestCase
{
    public function setUp(): void
    {
        $this->constraint = $this->createMock(Constraint::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new PasswordValidator();
    }

    public function providePassword(): array
    {
        return [
            ["166@0713", false],
            ["", false],
            ["as \n\ras \tas", false],
            ["as@asAasdxyz", true]
        ];
    }

    /**
     * @dataProvider providePassword
     */
    public function testPassword(string $password, bool $expected): void
    {
        $this->validator->validate($password, new Password());
        if ($expected) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation("This is not a valid password. A password should have:" . PHP_EOL .
                "- at least 8 alphanumeric characters" . PHP_EOL .
                "- at least one uppercase letter" . PHP_EOL .
                "- at least one special character" . PHP_EOL .
                "- should not include spaces, tabs, whitespaces")->assertRaised();
        }
    }

    public function testUnexpectedTypeExceptionValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('as@asAasdxyz', $this->constraint);
    }

    public function testUnexpectedValueExceptionConstraint(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(new \DateTime('-1 day'), new Password());
    }
}
