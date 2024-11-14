<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use JMS\Serializer\SerializationContext;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

class ArticleController extends AbstractController
{
    #[Route(path: "api/article", name:"api_article_index", methods: ["GET"])]
    #[OA\Response(
        response: 200,
        description: 'Return all articles',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Article::class, groups: ['getArticle']))
        )
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'get page to display',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Tag(name:"article")]
    public function indexArticle(Request $request, ArticleRepository $articleRepo, SerializerInterface $serializer, PaginatorInterface $paginator): JsonResponse
    {
        if(!$this->getUser()){
            return new JsonResponse($serializer->serialize(['message' => 'You must login to access this page'],'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }
    
        $articles = $articleRepo->findBy([], []);

        $articlesPager = $paginator->paginate(
            $articles,
            $request->query->getInt('page', 1),
            2
        );

        $data = [];

        foreach ($articlesPager as $key => $value) {
            
            $dataItem = ['articles' => $value];
            $data[] = $dataItem;
        }

        $getData = [
            'data' => $data,
            'current_page_number' => $articlesPager->getCurrentPageNumber(),
            'number_per_page' => $articlesPager->getItemNumberPerPage(),
            'total_count' => $articlesPager->getTotalItemCount()
        ];

        $context =  SerializationContext::create()->setGroups('getArticle');

        // dd($getData);

        $jsonArticles = $serializer->serialize($getData, 'json', $context);

        return new JsonResponse($jsonArticles, Response::HTTP_OK, [], true);
    }

    #[Route(path: "api/category/{id}/article/new", name:"api_article_add", methods: ["POST"])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                type: 'object',
                required: ['title', 'content', 'image'], // Assuming these are required fields
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        description: 'The title of the article'
                    ),
                    new OA\Property(
                        property: 'content',
                        type: 'string',
                        description: 'The content of the article'
                    ),
                    new OA\Property(
                        property: 'imageName',
                        type: 'string',
                        format: 'binary',
                        description: 'The image for the article (file upload)'
                    )
                ]
            )
        )
    )]
    #[OA\Tag(name:"article")]
    public function addArticle(Request $request, Category $category, ValidatorInterface $validator, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if(!$user){
            return new JsonResponse($serializer->serialize(['message' => 'You must login to access this page'],'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }

        $articleRequest = $request->request->all();
        $file =  $request->files->get('imageName');

        $article = new Article();

        if(!empty($file)){
            $fileExtensions = ["png", "jpg", "jpeg"];
            $extension = $file->guessExtension();

            if(in_array(strtolower($extension), $fileExtensions, true)){
                $imageName = md5(uniqid('', true)) . '.' . $extension;

                $file->move($this->getparameter('app.image.dir'), $imageName);

                $article->setImageName($imageName);
            }
        }

        $article->setTitle($articleRequest['title']);
        $article->setContent($articleRequest['content']);
        $article->setCategory($category);
        $article->setUser($user);

        $em->persist($article);
        $em->flush();

        $em->persist($article);
        $em->flush();

        $jsonArticle = $serializer->serialize(['message' => 'Your article has been created.'], 'json');

        return new JsonResponse($jsonArticle, Response::HTTP_CREATED, [], true);
    }

    #[Route(path: "api/article/{id}/delete", name:"api_article_delete", methods: ["DELETE"])]
    #[OA\Tag(name:"article")]
    public function deleteArticle(Request $request, Article $article, SerializerInterface $serializer, EntityManagerInterface $em): jsonResponse
    {
        if(!$this->getUser() || $this->getUser() !== $article->getUser()){
            return new JsonResponse($serializer->serialize(['message' => 'Access denied'], 'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }

        $em->remove($article);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}