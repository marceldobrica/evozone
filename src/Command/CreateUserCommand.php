<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\UserCreatedEvent;
use App\Repository\UserRepository;
use Symfony\Component\Console\Question\Question;
use App\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    protected static $defaultDescription = 'Add user account';

    private string $plainPassword;

    private UserRepository $userRepository;

    private ValidatorInterface $validator;

    private UserPasswordHasherInterface $passwordHasher;

    private EventDispatcherInterface $dispatcher;

    public function __construct(
        UserRepository $userRepository,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        EventDispatcherInterface $dispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
        $this->dispatcher = $dispatcher;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail address')
            ->addArgument('firstName', InputArgument::REQUIRED, 'First name')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Last name')
            ->addArgument('cnp', InputArgument::REQUIRED, 'CNP')
            ->addOption(
                'role',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'User role',
                ['ROLE_ADMIN']
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $question = new Question('Please enter the user\'s password:');
        $question->setHidden(true);
        $this->plainPassword = $this->getHelper('question')->ask($input, $output, $question);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = new User();
        $user->email = $input->getArgument('email');
        $user->firstName = $input->getArgument('firstName');
        $user->lastName = $input->getArgument('lastName');
        $user->cnp = $input->getArgument('cnp');
        $user->setRoles($input->getOption('role'));
        $user->setPassword($this->passwordHasher->hashPassword($user, $this->plainPassword));

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $io->error($error->getPropertyPath() . '-' . $error->getMessage());
            }
            return self::FAILURE;
        }

        $this->userRepository->add($user);
        $this->dispatcher->dispatch(new UserCreatedEvent($user), UserCreatedEvent::NAME);
        $io->success('You have created an user');

        return self::SUCCESS;
    }
}
