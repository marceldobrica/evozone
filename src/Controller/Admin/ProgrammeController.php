<?php

namespace App\Controller\Admin;

use App\Controller\ReturnValidationErrorsTrait;
use App\Entity\Programme;
use App\Form\Type\DeleteCancelType;
use App\Form\Type\ProgrammeType;
use App\Repository\ProgrammeRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/admin/programmes")
 */
class ProgrammeController extends AbstractController
{
    use ReturnValidationErrorsTrait;

    private ProgrammeRepository $programmeRepository;

    private RoomRepository $roomRepository;

    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private int $articlesOnPage;

    public function __construct(
        ProgrammeRepository $programmeRepository,
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        string $articlesOnPage
    ) {
        $this->programmeRepository = $programmeRepository;
        $this->roomRepository = $roomRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->articlesOnPage = intval($articlesOnPage);
    }

    /**
     * @Route(name="admin_programme", methods={"GET"})
     */
    public function showProgrammesAction(Request $request): Response
    {
        $currentPage = $request->query->get('page', 1);
        $pageSize = $request->query->get('size', $this->articlesOnPage);
        $nrProgrammes = $this->programmeRepository->countProgrammes();
        $currentPosition = ($currentPage - 1) * $pageSize;
        $nextPage = ($currentPosition + $pageSize < $nrProgrammes) ? $currentPage + 1 : $currentPage;
        $previousPage = ($currentPosition >= $pageSize) ? $currentPage - 1 : $currentPage;
        $programmes = $this->programmeRepository->findAllPaginated($currentPage, $pageSize);

        return $this->render('admin/programme/index.html.twig', [
            'programmes' => $programmes,
            'previous_page' => $previousPage,
            'next_page' => $nextPage,
            'current_page' => $currentPage,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * @Route("/add", name="admin_programme_add", methods={"GET", "POST"})
     */
    public function addProgrammeAction(Request $request): Response
    {
        $programme = new Programme();
        $form = $this->createForm(ProgrammeType::class, $programme);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $programme = $form->getData();
            if (!is_null($programme->getTrainer())) {
                if (
                    !empty(
                        $this->programmeRepository->isUserOcupiedAsTrainer(
                            $programme->getStartDate(),
                            $programme->getEndDate(),
                            $programme->getTrainer()->getId()
                        )
                    ) ||
                    !empty(
                        $this->programmeRepository->isUserOcupiedAsCustomer(
                            $programme->getStartDate(),
                            $programme->getStartDate(),
                            $programme->getTrainer()->getId()
                        )
                    )
                ) {
                    $programme->setTrainer(null);
                    $this->addFlash(
                        'warning',
                        'The picked trainer is already busy! We have set trainer to null!'
                    );
                }
            }

            if (is_null($programme->getRoom())) {
                $programme->setRoom(
                    $this->roomRepository->getRoomForProgramme(
                        $programme->getStartDate(),
                        $programme->getEndDate(),
                        $programme->isOnline,
                        $programme->maxParticipants
                    )
                );
            }
            $errors = $this->validator->validate($programme);
            if (count($errors) > 0) {
                return $this->returnValidationErrors($errors);
            }

            $this->entityManager->persist($programme);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'You have created a new programme!'
            );

            return $this->redirectToRoute('admin_programme');
        }

        return $this->renderForm('admin/programme/form.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="admin_programme_delete", methods={"GET", "POST"})
     */
    public function deleteProgrammeAction(Request $request, $id): Response
    {
        $programme = $this->programmeRepository->findOneBy(['id' => $id]);
        if (null === $programme) {
            return new Response('Programme not found!', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(DeleteCancelType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('delete')->isClicked()) {
                $this->entityManager->remove($programme);
                $this->entityManager->flush();
                $this->addFlash(
                    'info',
                    'You have deleted the user with id=' . $id . '!'
                );
            }

            return $this->redirectToRoute('admin_programme');
        }

        return $this->renderForm('admin/programme/delete_form.html.twig', [
            'form' => $form,
            'programme' => $programme,
        ]);
    }

    /**
     * @Route("/update/{id}", name="admin_programme_update", methods={"GET", "POST"})
     */
    public function updateProgrammeAction(Request $request, $id): Response
    {
        $programme = $this->programmeRepository->findOneBy(['id' => $id]);
        if (null === $programme) {
            return new Response('Programme not found!', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(ProgrammeType::class, $programme);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Programme */
            $programme = $form->getData();
            $errors = $this->validator->validate($programme);
            if (count($errors) > 0) {
                return $this->returnValidationErrors($errors);
            }
            $this->entityManager->persist($programme);
            $this->entityManager->flush();
            $this->addFlash(
                'info',
                'You have updated the programme with id=' . $id . '!'
            );

            return $this->redirectToRoute('admin_programme');
        }

        return $this->renderForm('admin/programme/form.html.twig', [
            'form' => $form,
        ]);
    }
}
