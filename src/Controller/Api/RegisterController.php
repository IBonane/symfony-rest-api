<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
// use Nelmio\ApiDocBundle\Attribute\Security as ns;

class RegisterController extends AbstractController
{
    #[Route(path: "api/register", name:"api_register", methods: ["POST"])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['email', 'firstname', 'lastname', 'password'],
            properties: [
                new OA\Property(
                    property: 'email',
                    description: 'email for user',
                    type: 'string'
                ),
                new OA\Property(
                    property: 'firstname',
                    description: 'firstname for user',
                    type: 'string'
                ), 
                new OA\Property(
                    property: 'lastname',
                    description: 'lastname for user',
                    type: 'string'
                ), 
                new OA\Property(
                    property: 'password',
                    description: 'password for user',
                    type: 'string'
                ), 
            ],
            example: [
                "email" => "johndoe@mail.com",
                "firstname" => "Jhon",
                "lastname" => "Doe",
                "password" => "*****************"
            ]
        )
    )]
    #[OA\Tag(name:"login and register")]
    public function ApiRegister(Request $request, ValidatorInterface $validator, SerializerInterface $serializer, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        if($this->getUser()){

            // Serialize the message without passing the HTTP status code directly
            $jsonContent = $serializer->serialize(
                ['message' => 'You must logout to access the register page'],
                'json',
                SerializationContext::create()
            );

            // Return the response with the serialized content and HTTP status
            return new JsonResponse($jsonContent, Response::HTTP_UNAUTHORIZED, [], true);
        }

        $newUser = $serializer->deserialize($request->getContent(), User::class, 'json');

        $error = $validator->validate($newUser);

        if($error->count() > 0){
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $plainPassword = $newUser->getPassword();
        
        // $user = $this->getUser();

        // encode the plain password
        $newUser->setPassword($userPasswordHasher->hashPassword($newUser, $plainPassword));

        $entityManager->persist($newUser);
        $entityManager->flush();

        return new JsonResponse($serializer->serialize(['message' => 'You account has been created.'], 'json'), Response::HTTP_OK, ['accept'=>'application/json'], true);
    }
}