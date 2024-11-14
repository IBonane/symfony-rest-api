<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;

class LoginController extends AbstractController
{
    
    #[Route(path: "api/login", name:"api_login", methods: ["POST"])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['username', 'password'],
            properties: [
                new OA\Property(
                    property: 'username',
                    description: 'username for user',
                    type: 'string'
                ), 
                new OA\Property(
                    property: 'password',
                    description: 'password for user',
                    type: 'string'
                ), 
            ],
            example: [
                "username" => "contact@mail.com",
                "password" => "*****************"
            ]
        )
    )]
    #[OA\Tag(name:"login and register")]
    public function ApiLogin()
    {
        $user = $this->getUser();

        $userData = [
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname()
        ];

        return $this->json($userData);
    }
}