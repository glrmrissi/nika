<?php

declare(strict_types=1);

namespace App\Command;

use App\Data\KanjiData;
use App\Entity\Kanji;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'app:kanji:seed', description: 'Seed the database with JLPT N5 to N1 kanji')]
class SeedKanjiCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('level', 'l', InputOption::VALUE_OPTIONAL, 'Filter by level (N5, N4, N3, N2, N1)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $levelFilter = $input->getOption('level');
        $allData = KanjiData::getAll();
        $count = 0;
        $seen = [];
        $skip = false;

        $existingChars = array_flip(
            $this->em->getRepository(Kanji::class)
                ->createQueryBuilder('k')
                ->select('k.character')
                ->getQuery()
                ->getSingleColumnResult()
        );

        foreach ($allData as $entry) {
            if (is_string($entry)) {
                $skip = true;
                continue;
            }

            if ($levelFilter && $entry[4] !== $levelFilter) {
                continue;
            }

            [$char, $mean, $onyomi, $kunyomi, $jlpt, $strokes] = $entry;

            if ($char === '') continue;

            if (isset($seen[$char])) {
                $output->writeln("  <comment>Duplicate in list: {$char}</comment>");
                continue;
            }
            $seen[$char] = true;

            if (isset($existingChars[$char])) {
                $output->writeln("  <comment>Already exists: {$char}</comment>");
                continue;
            }

            $kanji = new Kanji();
            $kanji->setCharacter($char);
            $kanji->setMeanings($mean);
            $kanji->setOnyomi($onyomi);
            $kanji->setKunyomi($kunyomi);
            $kanji->setJlptLevel($jlpt);
            $kanji->setStrokeCount($strokes);

            $this->em->persist($kanji);
            $count++;

            if ($count % 100 === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();

        $output->writeln("<info>Inserted {$count} kanji into the database.</info>");

        return Command::SUCCESS;
    }
}
