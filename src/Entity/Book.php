<?php

    namespace App\Entity;

    use App\Repository\BookRepository;
    use Doctrine\DBAL\Types\Types;
    use Doctrine\ORM\Mapping as ORM;
    //use Symfony\Component\Serializer\Annotation\Groups;
    use Hateoas\Configuration\Exclusion;
    use JMS\Serializer\Annotation\Groups;
    use JMS\Serializer\Annotation\Since;
    use Symfony\Component\Validator\Constraints as Assert;
    use Hateoas\Configuration\Annotation as Hateoas;

    // Lien d'auto-découvrabilité pour le GET {{id}}
    #[Hateoas\Relation(
        'self',
        href: new Hateoas\Route(
            'bookDetails',
            parameters: ['id' => 'expr(object.getId())']
        ),
        exclusion: new Hateoas\Exclusion(groups: ['getBooks'])
    )]

    // Lien d'auto-découvrabilité pour le PUT {{id}}
    #[Hateoas\Relation(
        'update',
        href: new Hateoas\Route(
            'updateBook',
            parameters: ['id' => 'expr(object.getId())']
        ),
        exclusion: new Hateoas\Exclusion(
            groups: ['getBooks'],
            excludeIf: "expr(not is_granted('ROLE_ADMIN'))"
        )
    )]

    // Lien d'auto-découvrabilité pour le DELETE {{id}}
    #[Hateoas\Relation(
        'delete',
        href: new Hateoas\Route(
            'removeBook',
            parameters: ['id' => 'expr(object.getId())']
        ),
        exclusion: new Hateoas\Exclusion(
            groups: ['getBooks'],
            excludeIf: "expr(not is_granted('ROLE_ADMIN'))"
        )
    )]

    #[ORM\Entity(repositoryClass: BookRepository::class)]
    class Book
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        #[Groups(['getBooks', 'getAuthors'])]
        private ?int $id = null;

        #[ORM\Column(length: 255)]
        #[Groups(['getBooks', 'getAuthors'])]
        #[Assert\NotBlank(message: 'Le titre du livre est obligatoire')]
        #[Assert\Length(
            min: 1,
            max: 128,
            minMessage: 'Le titre doit faire minimum {{ limit }} caractères',
            maxMessage: 'Le titre doit faire maximum {{ limit }} caractères'
        )]
        private ?string $title = null;

        #[ORM\Column(type: Types::TEXT, nullable: true)]
        #[Groups(['getBooks', 'getAuthors'])]
        #[Assert\NotBlank(message: 'Ajoutez une cover au livre')]
        private ?string $coverText = null;

        #[ORM\ManyToOne(inversedBy: 'books')]
        #[ORM\JoinColumn(onDelete: "CASCADE")]
        #[Groups(['getBooks'])]
        private ?Author $author = null;

        #[ORM\Column(type: Types::TEXT, nullable: true)]
        #[Groups(['getBooks'])]
        #[Since("2.0")]
        private ?string $comment = null;



        //Setters
        public function setTitle(string $title): static
        {
            $this->title = $title;

            return $this;
        }

        public function setCoverText(?string $coverText): static
        {
            $this->coverText = $coverText;

            return $this;
        }

        public function setComment(?string $comment): static
        {
            $this->comment = $comment;

            return $this;
        }



        //Getters

        public function getId(): ?int
        {
            return $this->id;
        }

        public function getTitle(): ?string
        {
            return $this->title;
        }

        public function getCoverText(): ?string
        {
            return $this->coverText;
        }

        public function getAuthor(): ?Author
        {
            return $this->author;
        }

        public function setAuthor(?Author $author): static
        {
            $this->author = $author;

            return $this;
        }

        public function getComment(): ?string
        {
            return $this->comment;
        }
    }
