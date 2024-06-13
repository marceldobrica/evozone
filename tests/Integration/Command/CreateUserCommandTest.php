<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    private UserRepository $userRepository;

    public function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);

        $container = static::getContainer();

        $this->userRepository = $container->get(UserRepository::class);

        $command = $application->find('app:create-user');

        $this->commandTester = new CommandTester($command);
    }

    public function testInvalidData(): void
    {
        self::expectException(MissingInputException::class);
        self::expectExceptionMessage('Aborted');

        $this->commandTester->execute([]);
    }

    public function testValidData(): void
    {
        $input = [
            'firstName' => 'Andrei',
            'lastName' => 'Voinicu',
            'cnp' => '5010911070069',
            'email' => 'email@email.com',
        ];
        $this->commandTester->setInputs($input);
        $this->commandTester->execute($input);

        $this->assertStringContainsString(
            'Please enter the user\'s password:',
            $this->commandTester->getDisplay()
        );
        $newUser = $this->userRepository->findOneBy(['email' => 'email@email.com']);
        $this->assertIsObject($newUser);
        $this->assertEquals('Andrei', $newUser->firstName);
        $this->assertEquals('Voinicu', $newUser->lastName);
        $this->assertEquals('5010911070069', $newUser->cnp);
    }

    public function testInValidInput(): void
    {
        $input = [
            'firstName' => 'Andrei',
            'lastName' => 'Voinicu',
            'cnp' => '123123',
            'email' => 'test@email.com',
        ];
        $this->commandTester->setInputs($input);
        $this->commandTester->execute($input);

        $this->assertStringContainsString(
            'Please enter the user\'s password:',
            $this->commandTester->getDisplay()
        );
        $newUser = $this->userRepository->findOneBy(['email' => 'test@email.com']);
        $this->assertNull($newUser);
    }
}
