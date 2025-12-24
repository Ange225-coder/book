<?php

    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Contracts\HttpClient\HttpClientInterface;

    class ExternalApiController extends AbstractController
    {
        public function __construct(
            private readonly HttpClientInterface $httpClient
        ){}


        #[Route(path: '/api/external/getSymfonyDoc', name: 'external_api', methods: ['GET'])]
        public function getSymfonyDoc(): JsonResponse
        {
            $response = $this->httpClient->request(
                'GET',
                'https://api.github.com/repos/symfony/symfony-docs'
            );

            return new JsonResponse($response->getContent(), $response->getStatusCode(), [], true);
        }
    }
