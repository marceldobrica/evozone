<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\Cnp;
use App\Validator\CnpValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class CnpValidatorTest extends ConstraintValidatorTestCase
{
    public function setUp(): void
    {
        $this->constraint = $this->createMock(Constraint::class);

        parent::setUp();
    }

    public function testTypeException(): void
    {
        $cnp = '1660713034972';

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate($cnp, $this->constraint);
    }

    public function testCnpLength(): void
    {
        $cnp = '1234';
        $this->validator->validate($cnp, new Cnp());

        $this->buildViolation('This is not a valid CNP')->assertRaised();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new CnpValidator();
    }

    public function provideCnp(): array
    {
        return [
            ['0', false],
            ['101', false],
            ['1660713034972', true],
            ['1660713034971', false],
            ['2720628034991', true],
        ];
    }

    /**
     * @dataProvider provideCnp
     */
    public function testCnp(string $cnp, bool $expectedValid): void
    {
        $this->validator->validate($cnp, new Cnp());
        if ($expectedValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('This is not a valid CNP')->assertRaised();
        }
    }

    public function testValidCnp(): void
    {
        $cnp = '1660713034972';
        $this->validator->validate($cnp, new Cnp());
        $this->assertNoViolation();
    }

    public function testNullCnp(): void
    {
        $this->validator->validate(null, new Cnp());
        $this->buildViolation('This is not a valid CNP')->assertRaised();
    }

    public function testBlankCnp(): void
    {
        $this->validator->validate('', new Cnp());
        $this->buildViolation('This is not a valid CNP')->assertRaised();
    }

    public function testValidNumberCnp(): void
    {
        $this->validator->validate(1660713034972, new Cnp());
        $this->assertNoViolation();
    }
}
