<?php

    namespace App\Controller;

    use App\Entity\Book;
    use App\Repository\AuthorRepository;
    use App\Repository\BookRepository;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\HttpFoundation\RequestStack;
    use Symfony\Component\HttpKernel\Exception\HttpException;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
    use Symfony\Component\Serializer\SerializerInterface;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    use Symfony\Contracts\Cache\ItemInterface;
    use Symfony\Contracts\Cache\TagAwareCacheInterface;

    class BookController extends AbstractController
    {
        public function __construct(
            private readonly BookRepository $bookRepository,
            private readonly SerializerInterface $serializer,
            private readonly EntityManagerInterface $entityManager,
            private readonly RequestStack $requestStack,
            private readonly UrlGeneratorInterface $urlGenerator,
            private readonly AuthorRepository $authorRepository,
            private readonly ValidatorInterface $validator,
            private readonly TagAwareCacheInterface $cache
        ){}


        // Récupérer tous les livres
        #[Route('/api/books', name: 'books', methods: ['GET'])]
        public function booksList(): JsonResponse
        {
            // Make pagination
            $request = $this->requestStack->getCurrentRequest();
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 3);

            // Cache system
            $idCache = 'booksList-'.$page.'-'.$limit;
            $jsonBooks = $this->cache->get($idCache, function (ItemInterface $item) use ($page, $limit) {
                // pour le debug
                echo ("pas encore en cache\n");
                $item->tag('booksListCache');
                $books = $this->bookRepository->findAllWithPagination($page, $limit);
                return $this->serializer->serialize($books, 'json', ['groups' => 'getBooks']);
            });

            return new JsonResponse($jsonBooks, Response::HTTP_OK, [], true);
        }


        // Récupérer un livre en fonction de son {{id}}
        #[Route('/api/books/{id}', name: 'bookDetails', methods: ['GET'])]
        public function bookDetails(Book $book): JsonResponse
        {
            $bookJson = $this->serializer->serialize($book, 'json', ['groups' => 'getBooks']);

            return new JsonResponse($bookJson, Response::HTTP_OK, [], true);
        }


        // Supprimer un livre en fonction de son {{id}}
        #[Route(path: '/api/books/{id}', name: 'removeBook', methods: ['DELETE'])]
        public function removeBook(Book $book): JsonResponse
        {
            $this->cache->invalidateTags(['booksListCache']);
            $this->entityManager->remove($book);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }


        // Création d'un livre
        #[Route(path: '/api/books', name: 'createBook', methods: ['POST'])]
        #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un livre')]
        public function createBook(): JsonResponse
        {
            $request = $this->requestStack->getCurrentRequest();

            $book = $this->serializer->deserialize($request->getContent(), Book::class, 'json');

            // Bind an author to the created book
            $content = $request->toArray();
            $idAuthor = $content['idAuthor'] ?? -1;
            $book->setAuthor($this->authorRepository->find($idAuthor));

            // Error handler
            $error = $this->validator->validate($book);
            if ($error->count() > 0) {
                $errorJson = $this->serializer->serialize($error, 'json');
                return new JsonResponse($this->serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
                //throw new HttpException(Response::HTTP_BAD_REQUEST, $errorJson);
            }

            $this->entityManager->persist($book);
            $this->entityManager->flush();

            // Get book created
            $bookCreated = $this->serializer->serialize($book, 'json', ['groups' => 'getBooks']);

            // Location
            $location = $this->urlGenerator->generate('bookDetails', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse($bookCreated, Response::HTTP_CREATED, ['Location' => $location], true);
        }


        // Mettre à jour un livre
        #[Route(path: '/api/books/{id}', name: 'updateBook', methods: ['PUT'])]
        public function updateBook(Book $currentBook): JsonResponse
        {
            $request = $this->requestStack->getCurrentRequest();
            $this->serializer->deserialize($request->getContent(), Book::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]);

            // Bind an author to this book
            $content = $request->toArray();
            $idAuthor = $content['idAuthor'] ?? -1;
            $currentBook->setAuthor($this->authorRepository->find($idAuthor));

            // Validation handler
            $errors = $this->validator->validate($currentBook);
            if ($errors->count() > 0) {
                return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            $this->entityManager->persist($currentBook);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
    }
