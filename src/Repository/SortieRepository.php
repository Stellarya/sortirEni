<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    public $criteria;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);

        $this->criteria = Criteria::create()
            ->orderBy(array("dateHeureDebut" => Criteria::DESC))
        ;
    }


    /**
     * @param $siteID
     * @param $resultatsMax
     * @param $numPage
     * @return int|mixed|string
     */
    public function findSortiesParSite($siteID, $resultatsMax, $numPage){
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.etat', 'e')
            ->where($qb->expr()->eq('e.id', '2'))
            ->join('s.site', 'si')
            ->andWhere($qb->expr()->eq('si.id', '?1'))
        ;
        $query = $qb->getQuery()
            ->setParameter(1, $siteID);

        return $query->getResult();

        $firstResult = ($numPage - 1) * $resultatsMax;
        $query->setFirstResult($firstResult)->setMaxResults($resultatsMax);

        return $query->getResult();
    }

    public function countSorties()
    {
        try {
            $qb = $this->createQueryBuilder('s');
            $qb->join('s.etat', 'e')
                ->where($qb->expr()->eq('e.id', '2'))
                ->select('count(s.id)');
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * @param $id
     * @param $resultatsMax
     * @param $numPage
     * @return Sortie[] Returns an array of Sortie objects
     * @throws QueryException
     */
    public function findSortiesByOrganisateur($id): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.organisateur', 'o')
            ->join('s.etat', 'e')
            ->where($qb->expr()->eq('o.id', $id))
            ->andWhere($qb->expr()->eq('e.id', '2'))
             ;

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param $texte
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesParTexte($texte): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->like('s.nom', '?1'))
             ;
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
        var_dump($dateDebut->format('Y-m-d H:i:s'), $dateFin->format('Y-m-d H:i:s'));
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->gte('s.dateHeureDebut', '?1'))
            ->andWhere($qb->expr()->lte('s.dateHeureDebut', '?2'));
        $query = $qb->getQuery();
        $query->setParameter(1, $dateDebut->format('Y-m-d H:i:s'));
        $query->setParameter(2, $dateFin->format('Y-m-d H:i:s'));
        return $query->getResult();
    }

    /**
     * @param $dateDebut
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesApresUneDate($dateDebut): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->gte('s.dateHeureDebut', '?1'))
             ;
        $query = $qb->getQuery();
        $query->setParameter(1, $dateDebut->format('Y-m-d H:i:s'));
        return $query->getResult();
    }

    /**
     * @param $dateFin
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesAvantUneDate($dateFin): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->lte('s.dateHeureDebut', '?1'))
             ;
        $query = $qb->getQuery();
        $query->setParameter(1, $dateFin->format('Y-m-d H:i:s'));
        return $query->getResult();
    }

    /**
     * @param $date
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesParDatePassee($date): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where($qb->expr()->lt('s.dateHeureDebut', '?1'))
             ;
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
            ->andWhere($qb->expr()->eq('e.id', '2'))
             ;
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
                ORDER BY s.dateHeureDebut DESC
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
            ->where($qb->expr()->eq('e.id', '2'))
             ;
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
