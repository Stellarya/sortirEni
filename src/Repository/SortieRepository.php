<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    /**
     * @return int|mixed|string
     */
    public function findSortiesParSite($siteID){
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.etat', 'e')
            ->where($qb->expr()->eq('e.id', '2'))
            ->join('s.site', 'si')
            ->andWhere($qb->expr()->eq('si.id', '?1'));
        $query = $qb->getQuery()->setParameter(1, $siteID);
        return $query->getResult();
    }

    /**
     * @param $id
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesByOrganisateur($id): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.organisateur', 'o')
            ->join('s.etat', 'e')
            ->where($qb->expr()->eq('o.id', $id))
            ->andWhere($qb->expr()->eq('e.id', '2'));
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $texte
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesParTexte($texte): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->like('s.nom', '?1'));
        $query = $qb->getQuery();
        $query->setParameter(1, '%'.$texte.'%');
        return $query->getResult();
    }

    /**
     * @param $dateDebut
     * @param $dateFin
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesEntreDeuxDates($dateDebut, $dateFin): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->gte('s.dateHeureDebut', '?1'))
            ->andWhere($qb->expr()->lte('s.dateHeureDebut', '?2'));
        $query = $qb->getQuery();
        $query->setParameter(1, $dateDebut);
        $query->setParameter(2, $dateFin);
        return $query->getResult();
    }

    /**
     * @param $dateDebut
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesApresUneDate($dateDebut): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->gte('s.dateHeureDebut', '?1'));
        $query = $qb->getQuery();
        $query->setParameter(1, $dateDebut);
        return $query->getResult();
    }

    /**
     * @param $dateFin
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesAvantUneDate($dateFin): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->lte('s.dateHeureDebut', '?1'));
        $query = $qb->getQuery();
        $query->setParameter(1, $dateFin);
        return $query->getResult();
    }

    /**
     * @param $date
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesParDatePassee($date): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->lt('s.dateHeureDebut', '?1'));
        $query = $qb->getQuery();
        $query->setParameter(1, $date);
        return $query->getResult();
    }

    /**
     * @param $id
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesByParticipant($id): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.participants', 'p')
            ->join('s.etat', 'e')
            ->where($qb->expr()->eq('p.id', $id))
            ->andWhere($qb->expr()->eq('e.id', '2'));
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $id
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesByParticipantPasInscrit($id): array
    {
        $dql = "SELECT s
                FROM App\Entity\Sortie s
                WHERE s.id NOT IN (
                    SELECT so
                    FROM App\Entity\Sortie so
                    JOIN so.participants pa
                    WHERE pa.id = ?1
                )";

        $em = $this->getEntityManager();
        $query = $em->createQuery($dql);
        $query->setParameter(1, $id);

        return $query->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findSortiesPubliees(){
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.etat', 'e')
            ->where($qb->expr()->eq('e.id', '2'));
        return $qb->getQuery()->getResult();
    }

    /*
    public function findOneBySomeField($value): ?Sortie
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
