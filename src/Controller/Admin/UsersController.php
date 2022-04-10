<?php

namespace App\Controller\Admin;

use App\Controller\ReturnValidationErrorsTrait;
use App\Entity\User;
use App\Form\Type\DeleteCancelType;
use App\Form\Type\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UsersController extends AbstractController
{
    use ReturnValidationErrorsTrait;

    private UserRepository $userRepository;

    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @Route("/admin/users", name="app_admin_users", methods={"GET"})
     */
    public function showUsersAction(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users
        ]);
    }

    /**
     * @Route("/admin/users/add", name="app_admin_users_add", methods={"GET", "POST"})
     */
    public function addUserAction(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();

            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->renderForm('admin/users/form.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/admin/users/delete/{id}", name="app_admin_users_delete", methods={"GET", "POST"})
     */
    public function deleteUserAction(Request $request, $id): Response
    {
        $user = $this->userRepository->findOneBy(['id' => $id]);

        $form = $this->createForm(DeleteCancelType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('delete')->isClicked()) {
                $this->entityManager->remove($user);
                $this->entityManager->flush();
            }

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->renderForm('admin/users/delete_form.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/admin/users/update/{id}", name="app_admin_users_update", methods={"GET", "POST"})
     */
    public function updateUserAction(Request $request, $id): Response
    {
        $user = $this->userRepository->findOneBy(['id' => $id]);

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // ... perform some action, such as saving the task to the database

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->renderForm('admin/users/form.html.twig', [
            'form' => $form,
        ]);
    }
}
