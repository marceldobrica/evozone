<?php

declare(strict_types=1);

namespace App\Command;

use App\Builders\BuildProgrammeFromArray;
use App\Decrypters\CaesarDecrypter;
use App\HttpClient\HttpClientImportPogramme;
use App\Repository\ProgrammeRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportProgrammeFromUrlCommand extends Command
{
    protected static $defaultName = 'app:import:programme-url';

    protected static $defaultDescription = 'Import programme from url';

    private HttpClientImportPogramme $client;

    private CaesarDecrypter $caesarDecrypter;

    private BuildProgrammeFromArray $buildProgrammeFromArray;

    private ProgrammeRepository $programmeRepository;

    public function __construct(
        HttpClientImportPogramme $client,
        CaesarDecrypter $caesarDecrypter,
        BuildProgrammeFromArray $buildProgrammeFromArray,
        ProgrammeRepository $programmeRepository
    ) {
        $this->client = $client;
        $this->caesarDecrypter = $caesarDecrypter;
        $this->buildProgrammeFromArray = $buildProgrammeFromArray;
        $this->programmeRepository = $programmeRepository;

        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rawData = $this->client->fetchData();

        foreach ($rawData as $encryptedArray) {
            $programmeArray = $this->caesarDecrypter->decryptProgrammeArray($encryptedArray);

            try {
                $programme = $this->buildProgrammeFromArray->build($programmeArray);
                $io->success('You have created the programme: ' . $programme->name);
                $this->programmeRepository->add($programme);
            } catch (\Exception $e) {
                $io->error('You were not able to create the programme: ' . $programmeArray['name']);
            }
        }

        return self::SUCCESS;
    }
}
