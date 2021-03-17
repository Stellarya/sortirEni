<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Participant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Participant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Participant[]    findAll()
 * @method Participant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * @param Sortie $oSortie
     * @return Participant[] Returns an array of Sortie objects
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function findParticipantsBySortie(Sortie $oSortie): array
    {
        $connexion = $this->getEntityManager()->getConnection();

        $sql = "SELECT p.*
                FROM participant p
                INNER JOIN participant_sortie ps ON ps.participant_id = p.id
                INNER JOIN sortie s ON s.id = ps.sortie_id
                WHERE ps.sortie_id = :idSortie";

        $stmt = $connexion->prepare($sql);
        $stmt->execute(['idSortie' => $oSortie->getId()]);
        return $stmt->fetchAll();
    }

    /*
    public function findOneBySomeField($value): ?Participant
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
