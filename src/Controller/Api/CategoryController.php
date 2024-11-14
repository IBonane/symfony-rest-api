<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
// use Nelmio\ApiDocBundle\Attribute\Security;

class CategoryController extends AbstractController
{
    #[Route(path: "api/category", name:"api_categroy_index", methods: ["GET"])]
    #[OA\Response(
        response: 200,
        description: 'Return all categories',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Category::class, groups: ['getCategory']))
        )
    )]
    #[OA\Tag(name:"category")]
    public function indexCategory(CategoryRepository $categoryRepo, SerializerInterface $serializer): JsonResponse
    {
        if(!$this->getUser()){
            return new JsonResponse($serializer->serialize(['message' => 'You must login to access this page'],'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }
    
        $categories = $categoryRepo->findAll();

        $context =  SerializationContext::create()->setGroups('getCategory');

        $jsonCategories = $serializer->serialize($categories, 'json', $context);

        return new JsonResponse($jsonCategories, Response::HTTP_OK, [], true);
    }


    #[Route(path: "api/category/{id}/update", name:"api_categroy_update", methods: ["POST"])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['name'],
            properties: [
                new OA\Property(
                    property: 'name',
                    description: 'update name of category',
                    type: 'string'
                )
            ],
            example: [
                "name" => "Fourniture"
            ]
        )
    )]
    #[OA\Tag(name:"category")]
    public function categoryUpdate(Category $category, Request $request, CategoryRepository $categoryRepo, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        if(!$this->getUser()){
            return new JsonResponse($serializer->serialize(['message' => 'You must login to access this page'],'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }

        $categoryItem = $serializer->deserialize($request->getContent(), Category::class, 'json');

        $category->setName($categoryItem->getName());

        $em->flush();

        return new JsonResponse($serializer->serialize(['message' => 'Your category has been updated'],'json'), Response::HTTP_OK, [], true);
    }

    #[Route(path: "api/category/new", name:"api_categroy_add", methods: ["POST"])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['name'],
            properties: [
                new OA\Property(
                    property: 'name',
                    description: 'name of category',
                    type: 'string'
                )
            ],
            example: [
                "name" => "Fourniture"
            ]
        )
    )]
    #[OA\Tag(name:"category")]
    public function addCategory(Request $request, ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        if(!$this->getUser()){
            return new JsonResponse($serializer->serialize(['message' => 'You must login to access this page'],'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }

        $category = $serializer->deserialize($request->getContent(), Category::class, 'json');

        $error = $validator->validate($category);

        if($error->count() > 0){
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($category);
        $em->flush();

        $jsonCategory = $serializer->serialize($category, 'json');

        return new JsonResponse($jsonCategory, Response::HTTP_CREATED, [], true);
    }

    #[OA\Tag(name:"category")]
    #[Route(path: "api/category/{id}/delete", name:"api_categroy_delete", methods: ["DELETE"])]
    public function deleteCategory(Request $request, Category $category, SerializerInterface $serializer, EntityManagerInterface $em): jsonResponse
    {
        if(!$category){
            return new JsonResponse($serializer->serialize(['message' => 'Category not found'],'json'), Response::HTTP_BAD_REQUEST, [], true);

        }

        if(!$this->getUser()){
            return new JsonResponse($serializer->serialize(['message' => 'You must login to access this page'],'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }

        $em->remove($category);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}