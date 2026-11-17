<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une catégorie du catalogue (Roman, Policier, Informatique…).
 *
 * Une entité Doctrine est une classe PHP ordinaire : les attributs #[ORM\…]
 * disent à Doctrine comment la ranger dans la table « categorie ».
 */
#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[ORM\Index(name: 'idx_categorie_nom', columns: ['nom'])]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Morceau d'URL lisible : « science-fiction » */
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    /** Couleur hexadécimale, utilisée pour les couvertures et les badges */
    #[ORM\Column(length: 7, options: ['default' => '#6B7280'])]
    private string $couleur = '#6B7280';

    /** Classe d'icône Font Awesome */
    #[ORM\Column(length: 255, options: ['default' => 'fas fa-folder'])]
    private string $icone = 'fas fa-folder';

    /** Une catégorie inactive n'est pas affichée sur le site */
    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    /**
     * Une catégorie regroupe plusieurs livres (1 → N).
     * C'est le côté « inverse » : la clé étrangère est dans la table livre.
     *
     * @var Collection<int, Livre>
     */
    #[ORM\OneToMany(targetEntity: Livre::class, mappedBy: 'categorie')]
    private Collection $livres;

    public function __construct()
    {
        $this->livres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCouleur(): string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getIcone(): string
    {
        return $this->icone;
    }

    public function setIcone(string $icone): static
    {
        $this->icone = $icone;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, Livre>
     */
    public function getLivres(): Collection
    {
        return $this->livres;
    }

    public function addLivre(Livre $livre): static
    {
        if (!$this->livres->contains($livre)) {
            $this->livres->add($livre);
            $livre->setCategorie($this);
        }

        return $this;
    }

    public function removeLivre(Livre $livre): static
    {
        if ($this->livres->removeElement($livre) && $livre->getCategorie() === $this) {
            $livre->setCategorie(null);
        }

        return $this;
    }

    /** Pour afficher une catégorie dans une liste déroulante : {{ categorie }} */
    public function __toString(): string
    {
        return (string) $this->nom;
    }
}
