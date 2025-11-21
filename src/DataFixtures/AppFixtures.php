<?php

    namespace App\DataFixtures;

    use App\Entity\Author;
    use App\Entity\Book;
    use App\Entity\User;
    use Doctrine\Bundle\FixturesBundle\Fixture;
    use Doctrine\Persistence\ObjectManager;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

    class AppFixtures extends Fixture
    {
        public function __construct(
            private readonly UserPasswordHasherInterface $passwordHasher
        ){}



        public function load(ObjectManager $manager): void
        {
            // Creation d'un utilisateur simple
            $user = new User();
            $user->setEmail('user@apibook.com');
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);


            // Creation d'un administrateur
            $admin = new User();
            $admin->setEmail('admin@apibook.com');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
            $manager->persist($admin);


            // Authors creation
            $listAuthors = [];
            for ($i = 1; $i < 10; $i++) {
                $author = new Author();
                $author->setFirstName('Prenom ' . $i);
                $author->setLastName('Nom ' . $i);

                $manager->persist($author);

                // save the author in the array
                $listAuthors[] = $author;
            }



            for ($i = 1; $i < 20; $i++) {
                $book = new Book();
                $book->setTitle('titre ' . $i);
                $book->setCoverText('Cover text number' . $i);
                $book->setAuthor($listAuthors[array_rand($listAuthors)]);

                $manager->persist($book);
            }

            $manager->flush();
        }
    }
