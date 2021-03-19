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
     * @return int|mixed|string
     * @throws QueryException
     */
    public function findSortiesParSite($siteID){
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.etat', 'e')
            ->where($qb->expr()->eq('e.libelle', '?2'))
            ->orWhere($qb->expr()->eq('e.libelle', '?3'))
            ->join('s.site', 'si')
            ->andWhere($qb->expr()->eq('si.id', '?1'))
            ->addCriteria($this->criteria)
        ;
        $query = $qb->getQuery()
            ->setParameter(1, $siteID)
            ->setParameter(2, "Ouverte")
            ->setParameter(3, "Clôturée");

        return $query->getResult();
    }

    public function countSorties()
    {
        try {
            $qb = $this->createQueryBuilder('s');
            $qb->join('s.etat', 'e')
                ->where($qb->expr()->eq('e.libelle', '?2'))
                ->orWhere($qb->expr()->eq('e.libelle', '?3'))
                ->select('count(s.id)');
            $query = $qb->getQuery()
                ->setParameter(2, "Ouverte")
                ->setParameter(3, "Clôturée");
            return $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }
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
            ->where($qb->expr()->eq('e.libelle', '?2'))
            ->orWhere($qb->expr()->eq('e.libelle', '?3'))
            ->orWhere($qb->expr()->eq('e.libelle', '?4'))
            ->andWhere($qb->expr()->eq('o.id', $id))
            ->addCriteria($this->criteria)
             ;

        $query = $qb->getQuery()
            ->setParameter(2, "Ouverte")
            ->setParameter(3, "Clôturée")
            ->setParameter(4, "Créée")
        ;
        return $query->getResult();
    }

    /**
     * @param $texte
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesParTexte($texte): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.etat', 'e')
            ->where($qb->expr()->eq('e.libelle', '?2'))
            ->orWhere($qb->expr()->eq('e.libelle', '?3'))
            ->andWhere($qb->expr()->like('s.nom', '?1'))
            ->addCriteria($this->criteria)
             ;
        $query = $qb->getQuery();
        $query->setParameter(1, '%'.$texte.'%')
            ->setParameter(2, "Ouverte")
            ->setParameter(3, "Clôturée");
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
        $qb->join('s.etat', 'e')
            ->where($qb->expr()->eq('e.libelle', '?3'))
            ->orWhere($qb->expr()->eq('e.libelle', '?4'))
            ->andWhere($qb->expr()->gte('s.dateHeureDebut', '?1'))
            ->andWhere($qb->expr()->lte('s.dateHeureDebut', '?2'))
            ->addCriteria($this->criteria);
        $query = $qb->getQuery();
        $query->setParameter(1, $dateDebut->format('Y-m-d H:i:s'));
        $query->setParameter(2, $dateFin->format('Y-m-d H:i:s'))
            ->setParameter(3, "Ouverte")
            ->setParameter(4, "Clôturée");;
        return $query->getResult();
    }

    /**
     * @param $dateDebut
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesApresUneDate($dateDebut): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.etat', 'e')
            ->where($qb->expr()->eq('e.libelle', '?2'))
            ->orWhere($qb->expr()->eq('e.libelle', '?3'))
            ->andWhere($qb->expr()->gte('s.dateHeureDebut', '?1'))
            ->addCriteria($this->criteria)
             ;
        $query = $qb->getQuery();
        $query->setParameter(1, $dateDebut->format('Y-m-d H:i:s'))
            ->setParameter(2, "Ouverte")
            ->setParameter(3, "Clôturée");
        return $query->getResult();
    }

    /**
     * @param $dateFin
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesAvantUneDate($dateFin): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.etat', 'e')
            ->where($qb->expr()->eq('e.libelle', '?2'))
            ->orWhere($qb->expr()->eq('e.libelle', '?3'))
            ->andWhere($qb->expr()->lte('s.dateHeureDebut', '?1'))
            ->addCriteria($this->criteria)
             ;
        $query = $qb->getQuery();
        $query->setParameter(1, $dateFin->format('Y-m-d H:i:s'))
            ->setParameter(2, "Ouverte")
            ->setParameter(3, "Clôturée");
        return $query->getResult();
    }

    /**
     * @return Sortie[] Returns an array of Sortie objects
     * @throws QueryException
     */
    public function findSortiesParDatePassee(): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->join('s.etat', 'e')
            ->andWhere($qb->expr()->eq('e.libelle', '?2'))
            ->addCriteria($this->criteria)
             ;
        $query = $qb->getQuery();
        $query
            ->setParameter(2, "Activité terminée");
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
            ->where($qb->expr()->eq('e.libelle', '?2'))
            ->orWhere($qb->expr()->eq('e.libelle', '?3'))
            ->andWhere($qb->expr()->eq('p.id', $id))
            ->addCriteria($this->criteria)
             ;
        $query = $qb->getQuery()
            ->setParameter(2, "Ouverte")
            ->setParameter(3, "Clôturée");
        return $query->getResult();
    }

    /**
     * @param $id
     * @return Sortie[] Returns an array of Sortie objects
     */
    public function findSortiesByParticipantPasInscrit($id): array
    {
        $dql = "SELECT s
                FROM App\Entity\Sortie s
                WHERE s.etat IN (
                    SELECT et
                    FROM App\Entity\Etat et
                    WHERE et.libelle = ?2
                    OR et.libelle = ?3)
                AND s.id NOT IN (
                    SELECT so
                    FROM App\Entity\Sortie so
                    JOIN so.participants pa
                    WHERE pa.id = ?1)
                ORDER BY s.dateHeureDebut DESC
                ";

        $em = $this->getEntityManager();
        $query = $em->createQuery($dql);
        $query->setParameter(1, $id)
            ->setParameter(2, "Ouverte")
            ->setParameter(3, "Clôturée")
        ;

        return $query->getResult();
    }

    /**
     * @return int|mixed|string
     */
    public function findSortiesPubliees(){
        $qb = $this->createQueryBuilder('s');
        $qb->join('s.etat', 'e')
            ->where($qb->expr()->eq('e.id', '2'))
            ->addCriteria($this->criteria)
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
