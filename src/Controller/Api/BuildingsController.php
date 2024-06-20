<?php

namespace App\Controller\Api;

use App\Controller\Dto\BuildingDto;
use App\Controller\ReturnValidationErrorsTrait;
use App\Entity\Building;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route (path="/api/buildings")
 */
class BuildingsController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    use ReturnValidationErrorsTrait;

    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @Route(methods={"POST"})
     */
    public function createBuildingAction(BuildingDto $buildingDto): Response
    {
        $building = Building::createFromDto($buildingDto);
        $errors = $this->validator->validate($building);
        if (count($errors) > 0) {
            return $this->returnValidationErrors($errors);
        }

        $this->entityManager->persist($building);
        $this->entityManager->flush();
        $this->entityManager->refresh($building);

        $newBuildingDto = BuildingDto::createFromBuilding($building);
        $this->logger->info('A Building was registered and saved in DB');

        return new JsonResponse($newBuildingDto, Response::HTTP_CREATED);
    }
}
