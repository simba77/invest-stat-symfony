<?php

declare(strict_types=1);

namespace App\Shared\Application\Controller;

use App\Shared\Application\UseCases\Homepage\GetHomepageDataUseCase;
use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class HomepageController extends AbstractController
{
    public function __construct(
        private readonly GetHomepageDataUseCase $getHomepageDataUseCase,
    ) {
    }

    #[Route('/dashboard', name: 'app_homepage_index')]
    public function index(#[CurrentUser] ?User $user): Response
    {
        return $this->json($this->getHomepageDataUseCase->execute($user));
    }
}
