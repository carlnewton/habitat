<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(name: 'habitat:reset-admin-password')]
class ResetAdminPasswordCommand extends AbstractLanguageCommand
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct($entityManager, $translator);
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->title($this->trans('commands.reset_admin_password.title'));

        $userRepository = $this->entityManager->getRepository(User::class);

        $admins = $userRepository->findUsersByRole('ROLE_SUPER_ADMIN');
        if (empty($admins)) {
            $io->error($this->trans('commands.reset_admin_password.no_admin'));

            return Command::FAILURE;
        }
        $admin = $admins[0];

        $io->info($this->trans('fields.password.help_text'));

        $password = $io->askHidden($this->trans('fields.new_password.title'), function (string $password): string {
            if (!User::isPasswordStrong($password)) {
                throw new \RuntimeException($this->trans('fields.password.validations.weak_password'));
            }

            return $password;
        });

        $hashedPassword = $this->passwordHasher->hashPassword($admin, $password);
        $admin->setPassword($hashedPassword);
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success($this->trans('fields.password.updated'));

        return Command::SUCCESS;
    }
}
