<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GroupeRepository::class)
 */
class Groupe
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Participant::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private Participant $owner;

    /** @ORM\Column(type="string", length=255) */
    private $libelle;

    /**
     * @ORM\ManyToMany(targetEntity=Participant::class, inversedBy="groupes")
     */
    private $participants;


    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    /**
     * @param ArrayCollection $participants
     */
    public function setParticipants(ArrayCollection $participants): void
    {
        $this->participants = $participants;
    }


    public function addParticipant(Participant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants[] = $participant;
            $participant->addGroupe($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        if ($this->participants->removeElement($participant)) {
            $participant->removeGroupe($this);
        }

        return $this;
    }

    /**
     * @return Participant
     */
    public function getOwner(): Participant
    {
        return $this->owner;
    }

    /**
     * @param Participant $owner
     */
    public function setOwner(Participant $owner): void
    {
        $this->owner = $owner;
    }




    /**
     * @return mixed
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * @param mixed $libelle
     */
    public function setLibelle($libelle): void
    {
        $this->libelle = $libelle;
    }


    public function hasParticipant(Participant $participant): bool {
        return $this->getParticipants()->contains($participant);
        //return in_array($participant->getId(), array_map(function(Participant $participant) { return $participant->getId(); }, $this->getParticipants()->toArray()));
    }



}
