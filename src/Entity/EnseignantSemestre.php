<?php

namespace App\Entity;

use App\Repository\EnseignantSemestreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnseignantSemestreRepository::class)]
class EnseignantSemestre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $semestre;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'enseignantSemestres')]
    private $user;

    #[ORM\Column(type: 'datetime')]
    private $closeDate;

    #[ORM\Column(type: 'string', length: 255)]
    private $session;

    #[ORM\Column(type: 'string', length: 255)]
    private $designation;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSemestre(): ?int
    {
        return $this->semestre;
    }

    public function setSemestre(int $semestre): self
    {
        $this->semestre = $semestre;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCloseDate(): ?\DateTimeInterface
    {
        return $this->closeDate;
    }

    public function setCloseDate(\DateTimeInterface $closeDate): self
    {
        $this->closeDate = $closeDate;

        return $this;
    }

    public function getSession(): ?string
    {
        return $this->session;
    }

    public function setSession(string $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): self
    {
        $this->designation = $designation;

        return $this;
    }

    
}
