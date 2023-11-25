<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuthController extends AbstractController
{
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
}
