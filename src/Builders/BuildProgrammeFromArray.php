<?php

declare(strict_types=1);

namespace App\Builders;

use App\Entity\Programme;
use App\Exception\NotAbleToAssignRoomException;
use App\Exception\NotValidProgrammeEntryException;
use App\Repository\RoomRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BuildProgrammeFromArray implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ValidatorInterface $validator;

    private RoomRepository $roomRepository;

    public function __construct(ValidatorInterface $validator, RoomRepository $roomRepository)
    {
        $this->validator = $validator;
        $this->roomRepository = $roomRepository;
    }

    /**
     * @throws NotAbleToAssignRoomException
     * @throws NotValidProgrammeEntryException
     */
    public function build(array $programmeArray): ?Programme
    {
        $programme = new Programme();
        $programme->name = $programmeArray['name'];
        $programme->description = $programmeArray['description'];
        $programme->setStartDate(\DateTime::createFromFormat('d.m.Y H:i', $programmeArray['startDate']));
        $programme->setEndDate(\DateTime::createFromFormat('d.m.Y H:i', $programmeArray['endDate']));
        $programme->isOnline = $programmeArray['isOnline'];
        $programme->maxParticipants = $programmeArray['maxParticipants'];
        $programme->setTrainer(null);
        $room = $this->roomRepository->getRoomForProgramme(
            \DateTime::createFromFormat('d.m.Y H:i', $programmeArray['startDate']),
            \DateTime::createFromFormat('d.m.Y H:i', $programmeArray['endDate']),
            $programmeArray['isOnline'],
            $programmeArray['maxParticipants']
        );

        if (!$room) {
            $message = 'Not able to assign a room to programme';
            $this->logger->warning($message, ['program' => \json_encode($programmeArray)]);

            throw new NotAbleToAssignRoomException();
        }

        $programme->setRoom($room);
        $errors = $this->validator->validate($programme);

        if (count($errors) > 0) {
            $message = 'Not valid programme entry';
            $this->logger->warning($message, ['program' => \json_encode($programmeArray)]);

            throw new NotValidProgrammeEntryException();
        }

        return $programme;
    }
}
