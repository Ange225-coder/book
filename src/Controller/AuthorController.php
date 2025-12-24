<?php

    namespace App\Controller;

    use App\Entity\Author;
    use App\Repository\AuthorRepository;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\RequestStack;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
    use JMS\Serializer\SerializerInterface;
    use JMS\Serializer\SerializationContext;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    use Symfony\Contracts\Cache\ItemInterface;
    use Symfony\Contracts\Cache\TagAwareCacheInterface;
    use OpenApi\Attributes as OA;
    use Nelmio\ApiDocBundle\Attribute\Model;

    class AuthorController extends AbstractController
    {
        public function __construct(
            private readonly AuthorRepository $authorRepository,
            //private readonly SerializerInterface $serializer,
            private readonly EntityManagerInterface $entityManager,
            private readonly RequestStack $requestStack,
            private readonly UrlGeneratorInterface $urlGenerator,
            private readonly ValidatorInterface $validator,
            private readonly TagAwareCacheInterface $cache,
            private readonly SerializerInterface $serializer
        ){}



        // Get all authors
        #[OA\Response(
            response: 200,
            description: 'Retourne la liste des auteurs',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: new Model(type: Author::class, groups: ['getAuthors']))
            )
        )]
//        /**
//         * Les OA\Parameters ici permettent de gérer les pages
//         * avec lesquels il y a de la pagination
//         */
        #[OA\Parameter(
            name: 'page',
            description: 'La page qu\'on veut récupérer',
            in: 'query',
            schema: new OA\Schema(type: 'int')
        )]

        #[OA\Parameter(
            name: 'limit',
            description: 'Le nombre d\'éléments qu\'on veut récupérer',
            in: 'query',
            schema: new OA\Schema(type: 'int')
        )]

        #[OA\Tag(name: 'Authors')]

        #[Route('/api/authors', name: 'authors', methods: ['GET'])]
        public function getAuthors(): JsonResponse
        {
//            $request = $this->requestStack->getCurrentRequest();
//            $page = $request->get('page', 1);
//            $limit = $request->get('limit', 3);
//
//           $idCache = 'get_all_authors_'.$page.'_'.$limit;
//           $authorsJson = $this->cache->get($idCache, function (ItemInterface $item) use ($page, $limit) {
//               //debug
//               echo ("Pas encore en cache\n");
//
//               $item->tag('authors_cache');
//               $authors = $this->authorRepository->findAllWithPagination($page, $limit);
//
//               $context = SerializationContext::create()->setGroups(['getAuthors']);
//               return $this->serializer->serialize($authors, 'json', $context);
//           });
//
//            return new JsonResponse($authorsJson, Response::HTTP_OK, [], true);

            // VERSION SANS CACHE

            // Pagination
            $request = $this->requestStack->getCurrentRequest();
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 3);

            $allAuthors = $this->authorRepository->findAllWithPagination($page, $limit);

            $context = SerializationContext::create()->setGroups(['getAuthors']);
            $allAuthorsJson = $this->serializer->serialize($allAuthors, 'json', $context);

            return new JsonResponse($allAuthorsJson, Response::HTTP_OK, [], true);
        }



        // Get author details
        #[OA\Response(
            response: 200,
            description: 'Permet de récupérer un auteur en fonction de son ID',
            content: new OA\JsonContent(
               ref: new Model(type: Author::class, groups: ['getAuthors'])
            )
        )]

        #[OA\Tag(name: 'Authors')]
        #[Route('/api/authors/{id}', name: 'authorDetails', methods: ['GET'])]
        public function getAuthorDetails(Author $author): JsonResponse
        {
            $idCache = 'get_author_details_'.$author->getId();
            $authorJson = $this->cache->get($idCache, function (ItemInterface $item) use ($author) {
                // debug
                echo ("Pas encore en cache\n");

                $item->tag('authors_cache');

                $context = SerializationContext::create()->setGroups(['getAuthors']);
                return $this->serializer->serialize($author, 'json', $context);
            });

            return new JsonResponse($authorJson, Response::HTTP_OK, [], true);
        }



        // Remove author
        #[OA\Response(
            response: 204,
            description: 'Supprimer un auteur en fonction de son ID',
        )]
        #[OA\Tag(name: 'Authors')]

        #[Route(path: '/api/authors/{id}', name: 'remove_author', methods: ['DELETE'])]
        public function removeAuthor(Author $author): JsonResponse
        {
            $this->cache->invalidateTags(['authors_cache']);

            $this->entityManager->remove($author);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }



        // Create an author
        #[OA\Post(
            path: '/api/authors',
            summary: 'Création d\'un nouvel auteur',
            requestBody: new OA\RequestBody(
                description: 'Données nécessaires pour créer un auteur',
                required: true,
                content: new OA\JsonContent(
                    ref: new Model(type: Author::class, groups: ['createAuthor'])
                )
            )
        )]
        #[OA\Response(
            response: 201,
            description: 'Utilisateur crée avec succès',
            content: new OA\JsonContent(
                ref: new Model(type: Author::class, groups: ['getAuthors'])
            )
        )]
        #[OA\Response(
            response: 400,
            description: 'Requête incorrect'
        )]
        #[OA\Tag('Authors')]
        #[Route(path: '/api/authors', name: 'create_author', methods: ['POST'])]
        public function createAuthor(): JsonResponse
        {
            $request = $this->requestStack->getCurrentRequest();
            $author = $this->serializer->deserialize($request->getContent(), Author::class, 'json');

            // Validation handler
            $errors = $this->validator->validate($author);
            if ($errors->count() > 0) {
                return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            $this->cache->invalidateTags(['authors_cache']);

            $this->entityManager->persist($author);
            $this->entityManager->flush();

            $location = $this->urlGenerator->generate('authorDetails', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            $context = SerializationContext::create()->setGroups(['getAuthors']);
            $authorCreated = $this->serializer->serialize($author, 'json', $context);

            return new JsonResponse($authorCreated, Response::HTTP_CREATED, ['Location' => $location], true);
        }



        // Update an author
        #[OA\Put(
            path: '/api/authors/{id}',
            summary: 'Mettre à jour un auteur',
            requestBody: new OA\RequestBody(
                description: 'Données pour mettre à jour l\'auteur',
                required: true,
                content: new OA\JsonContent(
                    ref: new Model(type: Author::class, groups: ['updateAuthor'])
                )
            ),
            parameters: [
                new OA\Parameter(
                    name: 'id',
                    description: 'identifiant de l\'auteur à modifier',
                    in: 'path',
                    required: true,
                    schema: new OA\Schema(
                        type: 'integer'
                    )
                )
            ]
        )]
        #[OA\Response(
            response: 204,
            description: 'Auteur mis à jour avec succès',
        )]
        #[OA\Response(
            response: 400,
            description: 'Requête incorrect'
        )]
        #[OA\Response(
            response: 404,
            description: 'Auteur non trouvé'
        )]
        #[OA\Tag(name: 'Authors')]
        #[Route(path: '/api/authors/{id}', name: 'update_author', methods: ['PUT'])]
        public function updateAuthor(Author $currentAuthor): JsonResponse
        {
            $request = $this->requestStack->getCurrentRequest();
            $authorUpdated = $this->serializer->deserialize($request->getContent(), Author::class, 'json');//, [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);

            if ($authorUpdated->getFirstName() !== null) {
                $currentAuthor->setFirstName($authorUpdated->getFirstName());
            }

            if ($authorUpdated->getLastName() !== null)  {
                $currentAuthor->setLastName($authorUpdated->getLastName());
            }

            // Validation handler
            $errors = $this->validator->validate($currentAuthor);
            if ($errors->count() > 0) {
                return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            $this->cache->invalidateTags(['authors_cache']);

            $this->entityManager->persist($currentAuthor);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
    }
