<?php

namespace App\Repository;

use App\Entity\Groupe;
use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Groupe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Groupe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Groupe[]    findAll()
 * @method Groupe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groupe::class);
    }


    /**
     * @param Participant $participant
     * @return Participant[]
     */
    public function findByParticipant(Participant $participant): array {
        $query =  $this->createQueryBuilder("g");
        $query->join("g.participants", "p")
            ->where($query->expr()->eq('p.id', $participant->getId()))
            ;
        return $query->getQuery()->getResult();
    }

    /**
     * @param Participant $participant
     * @return Participant[]
     */
    public function findByOwner(Participant $participant): array {
        $query =  $this->createQueryBuilder("g");
        $query->join("g.owner", "o")
            ->where($query->expr()->eq('o.id', $participant->getId()))
        ;
        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return Groupe[] Returns an array of Groupe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Groupe
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
