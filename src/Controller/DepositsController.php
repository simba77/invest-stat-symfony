<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Services\DepositsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class DepositsController extends AbstractController
{
    public function __construct(
        private readonly DepositsService $depositsService
    ) {
    }

    #[Route('/deposits', name: 'app_deposits_index', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user)
    {
        $deposits = $this->depositsService->getAllDepositsForUser($user);
        return $this->json(['items' => $deposits]);
    }
}
