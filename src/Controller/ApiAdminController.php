<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiAdminController
{
    /**
     * @Route("/api/admin",methods={"GET"})
     */
    public function index(): Response
    {

        return new JsonResponse([
            'message' => 'Welcome to your new controller - available only for ROLE_ADMIN!'
        ]);
    }
}
