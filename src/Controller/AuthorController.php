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
    use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
    use Symfony\Component\Serializer\SerializerInterface;
    use Symfony\Component\Validator\Validator\ValidatorInterface;

    class AuthorController extends AbstractController
    {
        public function __construct(
            private readonly AuthorRepository $authorRepository,
            private readonly SerializerInterface $serializer,
            private readonly EntityManagerInterface $entityManager,
            private readonly RequestStack $requestStack,
            private readonly UrlGeneratorInterface $urlGenerator,
            private readonly ValidatorInterface $validator
        ){}



        // Get all authors
        #[Route('/api/authors', name: 'authors', methods: ['GET'])]
        public function getAuthors(): JsonResponse
        {
            $authors = $this->authorRepository->findAll();

            $authorsJson = $this->serializer->serialize($authors, 'json', ['groups' => 'getAuthors']);

            return new JsonResponse($authorsJson, 200, [], true);
        }



        // Get author details
        #[Route('/api/authors/{id}', name: 'authorDetails', methods: ['GET'])]
        public function getAuthorDetails(int $id): JsonResponse
        {
            $author = $this->authorRepository->find($id);

            if ($author) {
                $authorJson = $this->serializer->serialize($author, 'json', ['groups' => 'getAuthors']);

                return new JsonResponse($authorJson, 200, [], true);
            }

            return new JsonResponse(['message' => 'Author non trouvé'], 404);
        }



        // Remove author
        #[Route(path: '/api/authors/{id}', name: 'remove_author', methods: ['DELETE'])]
        public function removeAuthor(int $id): JsonResponse
        {
            $author = $this->authorRepository->find($id);

            if ($author) {
                $this->entityManager->remove($author);
                $this->entityManager->flush();

                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
            }

            return new JsonResponse(['message' => 'Auteur non trouvé', Response::HTTP_NOT_FOUND]);
        }



        // Create an author
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

            $this->entityManager->persist($author);
            $this->entityManager->flush();

            $location = $this->urlGenerator->generate('authorDetails', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            $authorCreated = $this->serializer->serialize($author, 'json', ['groups' => 'getAuthors']);

            return new JsonResponse($authorCreated, Response::HTTP_CREATED, ['Location' => $location], true);
        }



        // Update an author
        #[Route(path: '/api/authors/{id}', name: 'update_author', methods: ['PUT'])]
        public function updateAuthor(Author $currentAuthor): JsonResponse
        {
            $request = $this->requestStack->getCurrentRequest();
            $this->serializer->deserialize($request->getContent(), Author::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);

            // Validation handler
            $errors = $this->validator->validate($currentAuthor);
            if ($errors->count() > 0) {
                return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
            }

            $this->entityManager->persist($currentAuthor);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
    }
