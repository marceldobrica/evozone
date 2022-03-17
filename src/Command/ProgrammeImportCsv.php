<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Programme;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProgrammeImportCsv extends Command
{
    private const CSV_ERRORS_FILE = 'csv_errors.csv';

    private const DEFAULT_OUTPUT_ERRORS_FOLDER = __DIR__ . '/Files';

    private int $programmeMinTimeInMinutes;

    private int $programmeMaxTimeInMinutes;

    private int $correctRows = 0;

    private int $wrongRows = 0;

    private EntityManagerInterface $entityManager;

    protected static $defaultName = 'app:programme:import-csv';

    protected static $defaultDescription = 'Import programmes from a csv file and generate a csv file for errors';

    public function __construct(
        string $programmeMinTimeInMinutes,
        string $programmeMaxTimeInMinutes,
        EntityManagerInterface $entityManager
    ) {
        $this->programmeMaxTimeInMinutes = intval($programmeMaxTimeInMinutes);
        $this->programmeMinTimeInMinutes = intval($programmeMinTimeInMinutes);
        $this->entityManager = $entityManager;

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
                'output folder for unimported rows'
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

        $outputFolder = $input->getOption('output-folder') ?: self::DEFAULT_OUTPUT_ERRORS_FOLDER;
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

        $message = sprintf('Successfully imported %d programmes and failed to import %d programmes.
            The generated csv file for error rows is %s', $this->correctRows, $this->wrongRows, $outputFile);
        $io->success($message);

        return self::SUCCESS;
    }

    /**
     * @param false|resource $readHandler
     * @param false|resource $writeHandler
     * @throws InvalidCSVHeaderException
     */
    private function handleResources($readHandler, $writeHandler): string
    {
        $receivedHeader = fgets($readHandler);
        if ($receivedHeader !== 'Name|Description|Start date|End date|Online') {
            throw new InvalidCSVHeaderException();
        }
        while (! feof($readHandler)) {
            $receivedRow = fgetcsv($readHandler, null, '|');
            if ($this->verifyRow($receivedRow)) {
                $this->writeToDatabase($receivedRow);
                $this->correctRows++;
            } else {
                $this->writeToErrorCSV($receivedRow, $writeHandler);
                $this->wrongRows++;
            }
        }

        return 'message';
    }

    public function verifyRow(array $receivedRow): bool
    {
        if (empty($receivedRow)) {
            return false;
        }
        if (count($receivedRow) !== 5) {
            return false;
        }
        if (empty($receivedRow['name'])) {
            return false;
        }
        if (!in_array(strtolower($receivedRow['Online']), ['da','nu'])) {
            return false;
        }
        $now = new \DateTime('now');
        $programmeStartDate = \DateTime::createFromFormat('d.m.Y H:i', $receivedRow['Start date']);
        if ($programmeStartDate < $now) {
            return false;
        }
        $programmeEndDate = \DateTime::createFromFormat('d.m.Y H:i', $receivedRow['End date']);
        if ($programmeEndDate < $now) {
            return false;
        }
        $interval = $programmeStartDate->diff($programmeEndDate);
        if ($interval->m < 0) {
            return false;
        }
        if ($this->programmeMinTimeInMinutes > $interval->m) {
            return false;
        }
        if ($this->programmeMaxTimeInMinutes < $interval->m) {
            return false;
        }

        return true;
    }

    public function writeToDatabase(array $row): void
    {
        $programme = new Programme();
        $programme->name = $row['Name'];
        $programme->description = $row['Description'];
        $programme->setStartDate(\DateTime::createFromFormat('d.m.Y H:i', $row['Start date']));
        $programme->setEndDate(\DateTime::createFromFormat('d.m.Y H:i', $row['End date']));
        $programme->isOnline = strtolower($row['Online']) === 'da';

        $this->entityManager->persist($programme);
        $this->entityManager->flush();
    }

    /**
     * @param false|resource $writeHandler
     */
    public function writeToErrorCSV(array $row, $writeHandler): void
    {
        echo 'to do';
    }
}
