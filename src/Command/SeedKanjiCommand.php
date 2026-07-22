<?php

namespace App\Command;

use App\Data\KanjiData;
use App\Entity\Kanji;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'app:kanji:seed', description: 'Popula o banco com kanji JLPT N5~N1')]
class SeedKanjiCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('level', 'l', InputOption::VALUE_OPTIONAL, 'Filtrar por nível (N5, N4, N3, N2, N1)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $levelFilter = $input->getOption('level');
        $allData = KanjiData::getAll();
        $count = 0;
        $seen = [];
        $skip = false;

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
                $output->writeln("  <comment>Duplicado na lista: {$char}</comment>");
                continue;
            }
            $seen[$char] = true;

            $existing = $this->em->getRepository(Kanji::class)->findOneBy(['character' => $char]);
            if ($existing) {
                $output->writeln("  <comment>Já existe: {$char}</comment>");
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
        }

        $this->em->flush();

        $output->writeln("<info>Inseridos {$count} kanji no banco.</info>");

        return Command::SUCCESS;
    }
}
