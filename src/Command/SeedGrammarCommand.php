<?php

declare(strict_types=1);

namespace App\Command;

use App\Data\GrammarData;
use App\Entity\GrammarParticle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:grammar:seed', description: 'Seed the database with grammar particles')]
class SeedGrammarCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $allData = GrammarData::getAll();
        $count = 0;
        $seen = [];

        $existingParticles = array_flip(
            $this->em->getRepository(GrammarParticle::class)
                ->createQueryBuilder('p')
                ->select('p.particle')
                ->getQuery()
                ->getSingleColumnResult()
        );

        foreach ($allData as $entry) {
            if (is_string($entry)) {
                continue;
            }

            [$particle, $romaji, $name, $meaning, $usageNote,
             $ex1, $ex1r, $ex1t, $ex2, $ex2r, $ex2t,
             $category, $sortOrder] = $entry;

            if ($particle === '') continue;

            if (isset($seen[$particle])) {
                $output->writeln("  <comment>Duplicate in list: {$particle}</comment>");
                continue;
            }
            $seen[$particle] = true;

            if (isset($existingParticles[$particle])) {
                $output->writeln("  <comment>Already exists: {$particle}</comment>");
                continue;
            }

            $gp = new GrammarParticle();
            $gp->setParticle($particle);
            $gp->setRomaji($romaji);
            $gp->setName($name);
            $gp->setMeaning($meaning);
            $gp->setUsageNote($usageNote);
            $gp->setExampleOne($ex1);
            $gp->setExampleOneReading($ex1r);
            $gp->setExampleOneTranslation($ex1t);
            $gp->setExampleTwo($ex2);
            $gp->setExampleTwoReading($ex2r);
            $gp->setExampleTwoTranslation($ex2t);
            $gp->setCategory($category);
            $gp->setSortOrder($sortOrder);

            $this->em->persist($gp);
            $count++;

            if ($count % 50 === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();

        $output->writeln("<info>Inserted {$count} grammar particles into the database.</info>");

        return Command::SUCCESS;
    }
}
