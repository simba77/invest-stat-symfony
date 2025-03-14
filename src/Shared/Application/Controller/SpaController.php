<?php

declare(strict_types=1);

namespace App\Shared\Application\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class SpaController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('spa.html.twig');
    }
}
