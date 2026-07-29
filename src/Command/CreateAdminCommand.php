<?php

namespace App\Command;

use App\Entity\ActivityType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:admin:create', description: 'Creates the admin user from ADMIN_EMAIL and ADMIN_PASSWORD env vars')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $_ENV['ADMIN_EMAIL'] ?? null;
        $password = $_ENV['ADMIN_PASSWORD'] ?? null;

        if (!$email || !$password) {
            $output->writeln('<error>ADMIN_EMAIL and ADMIN_PASSWORD must be set in .env</error>');
            return Command::FAILURE;
        }

        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $email]);

        if ($user) {
            $user->setPassword($this->hasher->hashPassword($user, $password));
            $output->writeln(sprintf('<info>Admin "%s" updated.</info>', $email));
        } else {
            $user = new User();
            $user->setEmail($email);
            $user->setName('Admin');
            $user->setRoles(['ROLE_ADMIN']);
            $user->setPassword($this->hasher->hashPassword($user, $password));
            $this->em->persist($user);
            $output->writeln(sprintf('<info>Admin "%s" created.</info>', $email));
        }

        $this->seedActivityTypes($output);

        $this->em->flush();

        return Command::SUCCESS;
    }

    private function seedActivityTypes(OutputInterface $output): void
    {
        $types = [
            ['name' => 'Kanji Review', 'slug' => 'review_kanji'],
            ['name' => 'Grammar Review', 'slug' => 'review_grammar'],
            ['name' => 'Grammar Quiz', 'slug' => 'quiz'],
        ];

        $repo = $this->em->getRepository(ActivityType::class);
        foreach ($types as $data) {
            $existing = $repo->findOneBy(['slug' => $data['slug']]);
            if (!$existing) {
                $type = new ActivityType();
                $type->setName($data['name']);
                $type->setSlug($data['slug']);
                $this->em->persist($type);
                $output->writeln(sprintf('<info>ActivityType "%s" created.</info>', $data['slug']));
            }
        }
    }
}
