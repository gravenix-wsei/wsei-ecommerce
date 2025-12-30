<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\Admin\Settings\AdminUsers;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wsei\Ecommerce\Entity\User;
use Wsei\Ecommerce\Form\Admin\AdminUserType;
use Wsei\Ecommerce\Framework\Admin\Settings\EcommerceSettingsInterface;
use Wsei\Ecommerce\Repository\UserRepository;

#[IsGranted('ROLE_SUPER_ADMIN')]
#[Route('/admin/settings/admin-users')]
class AdminUsersController extends AbstractController implements EcommerceSettingsInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function getName(): string
    {
        return 'Admin Users';
    }

    public function getIcon(): string
    {
        return 'users.svg';
    }

    public function getDescription(): ?string
    {
        return 'Manage administrator accounts and permissions';
    }

    public function getPosition(): int
    {
        return 100;
    }

    public function getPathEntrypointName(): string
    {
        return 'admin.settings.admin_users';
    }

    public function getRequiredRole(): ?string
    {
        return 'ROLE_SUPER_ADMIN';
    }

    #[Route('', name: 'admin.settings.admin_users', methods: ['GET'])]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('admin/pages/settings/admin_users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'admin.settings.admin_users.new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Administrator account created successfully.');

            return $this->redirectToRoute('admin.settings.admin_users');
        }

        return $this->render('admin/pages/settings/admin_users/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin.settings.admin_users.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $currentUser = $this->getUser();

        // Prevent user from editing their own account (safety check)
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot edit your own account.');
            return $this->redirectToRoute('admin.settings.admin_users');
        }

        $form = $this->createForm(AdminUserType::class, $user, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Only update password if provided
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Administrator account updated successfully.');

            return $this->redirectToRoute('admin.settings.admin_users');
        }

        return $this->render('admin/pages/settings/admin_users/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin.settings.admin_users.delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        $currentUser = $this->getUser();

        // Prevent user from deleting their own account (safety check)
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('admin.settings.admin_users');
        }

        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Administrator account deleted successfully.');
        }

        return $this->redirectToRoute('admin.settings.admin_users');
    }
}
