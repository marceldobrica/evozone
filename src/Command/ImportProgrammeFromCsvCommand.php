<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Programme;
use App\Exception\InvalidCSVHeaderException;
use App\Exception\NotAbleToAssignRoomException;
use App\Repository\ProgrammeRepository;
use App\Repository\RoomRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportProgrammeFromCsvCommand extends Command
{
    private const CSV_ERRORS_FILE = 'csv_errors.csv';

    private string $defaultFilesDirectory;

    private int $programmeMinTimeInMinutes;

    private int $programmeMaxTimeInMinutes;

    private int $correctRows = 0;

    private int $wrongRows = 0;

    private ProgrammeRepository $programmeRepository;

    protected static $defaultName = 'app:programme:import-csv';

    protected static $defaultDescription = 'Import programmes from a csv file and generate a csv file for errors';

    private RoomRepository $roomRepository;

    public function __construct(
        string $programmeMinTimeInMinutes,
        string $programmeMaxTimeInMinutes,
        string $defaultFilesDirectory,
        ProgrammeRepository $programmeRepository,
        RoomRepository $roomRepository
    ) {
        $this->programmeMaxTimeInMinutes = intval($programmeMaxTimeInMinutes);
        $this->programmeMinTimeInMinutes = intval($programmeMinTimeInMinutes);
        $this->defaultFilesDirectory = $defaultFilesDirectory;
        $this->programmeRepository = $programmeRepository;
        $this->roomRepository = $roomRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'path to file'
            )
            ->addOption(
                'output-folder',
                null,
                InputOption::VALUE_OPTIONAL,
                'output folder for un-imported rows'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $inputFile = $input->getOption('file');
        if (!$inputFile) {
            $io->error('File option is required');
            return self::FAILURE;
        }
        if (!file_exists($inputFile)) {
            $io->error('File does not exist');
            return self::FAILURE;
        }

        $outputFolder = $input->getOption('output-folder') ?: $this->defaultFilesDirectory;
        $outputFile = $outputFolder . '/' . self::CSV_ERRORS_FILE;

        $error = false;
        try {
            $readHandler = fopen($inputFile, 'r');
            $writeHandler = fopen($outputFile, 'w');
            $this->handleResources($readHandler, $writeHandler);
        } catch (InvalidCSVHeaderException $e) {
            $io->error($e->getMessage());
            $error = true;
        } catch (\Exception $e) {
            $io->error('Caught exception: ' . $e->getMessage());
            $error = true;
        } finally {
            if (isset($readHandler)) {
                fclose($readHandler);
            }
            if (isset($writeHandler)) {
                fclose($writeHandler);
            }
        }
        if ($error) {
            return self::FAILURE;
        }

        $message = sprintf(
            'Successfully imported %d programmes and failed to import %d programmes. 
            The generated csv file for error rows is %s',
            $this->correctRows,
            $this->wrongRows,
            $outputFile
        );
        $io->success($message);

        return self::SUCCESS;
    }

    /**
     * @param resource $readHandler
     * @param resource $writeHandler
     * @throws InvalidCSVHeaderException|NotAbleToAssignRoomException
     * @throws \Exception
     */
    private function handleResources($readHandler, $writeHandler): void
    {
        $receivedHeader = fgets($readHandler);
        if ($receivedHeader !== "Name|Description|Start date|End date|Online|MaxParticipants\n") {
            throw new InvalidCSVHeaderException();
        }
        while (!feof($readHandler)) {
            $receivedRow = fgetcsv($readHandler, null, '|');
            if ($this->verifyRow($receivedRow)) {
                $this->writeToDatabase($receivedRow);
                $this->correctRows++;
            } else {
                $this->writeToErrorCSV($receivedRow, $writeHandler);
                $this->wrongRows++;
            }
        }
    }

    private function verifyRow(array $receivedRow): bool
    {
        if (empty($receivedRow)) {
            return false;
        }
        if (count($receivedRow) !== 6) {
            return false;
        }
        if (empty($receivedRow[0])) {
            return false;
        }
        if (!in_array(strtolower($receivedRow[4]), ['da', 'nu'])) {
            return false;
        }
        $now = new \DateTime('now');
        $programmeStartDate = \DateTime::createFromFormat('d.m.Y H:i', $receivedRow[2]);
        if ($programmeStartDate < $now) {
            return false;
        }
        $programmeEndDate = \DateTime::createFromFormat('d.m.Y H:i', $receivedRow[3]);
        if ($programmeEndDate < $now) {
            return false;
        }
        if ($programmeEndDate < $programmeStartDate) {
            return false;
        }
        $interval = $programmeStartDate->diff($programmeEndDate);
        $minute = $interval->d * 24 * 60 + $interval->h * 60 + $interval->i;
        if ($this->programmeMinTimeInMinutes > $minute) {
            return false;
        }
        if ($this->programmeMaxTimeInMinutes < $minute) {
            return false;
        }

        return true;
    }

    /**
     * @throws NotAbleToAssignRoomException
     */
    private function writeToDatabase(array $row): void
    {
        $programme = new Programme();
        $programme->name = $row[0];
        $programme->description = $row[1];
        $programme->setStartDate(\DateTime::createFromFormat('d.m.Y H:i', $row[2]));
        $programme->setEndDate(\DateTime::createFromFormat('d.m.Y H:i', $row[3]));
        $programme->isOnline = strtolower($row[4]) === 'da';
        $programme->maxParticipants = intval($row[5]);
        $programme->setTrainer(null);
        $room = $this->roomRepository->getRoomForProgramme(
            $programme->getStartDate(),
            $programme->getEndDate(),
            $programme->isOnline,
            $programme->maxParticipants
        );

        if (!$room) {
            throw new NotAbleToAssignRoomException();
        }

        $programme->setRoom($room);
        $this->programmeRepository->add($programme);
    }

    /**
     * @param resource $writeHandler
     * @throws \Exception
     */
    private function writeToErrorCSV(array $row, $writeHandler): void
    {
        if (!fputcsv($writeHandler, $row, '|')) {
            throw new \Exception('Unable to write to csv error file');
        }
    }
}
