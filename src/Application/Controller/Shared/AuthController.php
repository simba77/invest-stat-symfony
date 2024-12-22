<?php

declare(strict_types=1);

namespace App\Application\Controller\Shared;

use App\Application\Request\DTO\Shared\ChangeProfileRequestDTO;
use App\Domain\Shared\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AuthController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/login', name: 'app_auth_login')]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($user, context: ['groups' => 'authUserData']);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
    }

    #[Route('/change-profile', name: 'app_auth_change_profile', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
    public function changeProfile(#[CurrentUser] ?User $user, #[MapRequestPayload] ChangeProfileRequestDTO $DTO): JsonResponse
    {
        $user->setName($DTO->name);
        $user->setEmail($DTO->email);
        $user->setSalary($DTO->salary);

        if (! empty($DTO->password)) {
            $password = $this->passwordHasher->hashPassword($user, $DTO->password);
            $user->setPassword($password);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }
}
