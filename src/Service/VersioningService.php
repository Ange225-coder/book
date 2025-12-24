<?php

    /**
     * Cette classe permet de modifier la version d'une requête
     * sous POSTMAN en passant par le header "Accept".
     */
    namespace App\Service;

    use Symfony\Component\HttpFoundation\RequestStack;
    use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

    class VersioningService
    {
        private string $defaultVersion;

        /**
         * @param RequestStack $requestStack
         * @param ParameterBagInterface $params
         * Ce dernier permet de récupérer des paramètres qui
         * ont été spécifié dans le fichier services.yaml
         */

        public function __construct(private readonly RequestStack $requestStack, ParameterBagInterface $params)
        {
            $this->defaultVersion = $params->get('default_api_version');
        }


        public function getVersion(): string
        {

            /**
             * Spécifier la version actuelle,
             * la version par défaut
             */
            $version = $this->defaultVersion;

            /**
             * Récupérer la requête actuelle, avec $request,
             * et à partir d'elle, récupérer le champ "Accept"
             * qui se trouve dans le header
             */
            $request = $this->requestStack->getCurrentRequest();
            $accept = $request->headers->get('Accept');

            /**
             * Séparer le champ "Accept" par des ;
             */
            $entete = explode(';', $accept);

            /**
             * On parcourt toutes les entêtes pour trouver une version
             *
             * Si on en trouve, on la récupère, sinon on utilise
             * la version par defaut (version spécifiée dans le fichier services.yaml)
             * default_api_version: "version"
             */
            foreach ($entete as $value) {
                if (str_contains($value, 'version')) {
                    $version = explode('=', $value);
                    $version = $version[1];
                    break;
                }
            }

            return $version;
        }
    }
