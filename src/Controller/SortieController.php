<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use App\Form\SortieFiltreType;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SortieController extends AbstractController
{
    public $siteID = null;

    /**
     * @Route("/sortie/{pageNumber}", name="page_sortie")
     * @param Request $request
     * @param SortieRepository $sortieRepository
     * @param UserRepository $userRepository
     * @param SessionInterface $session
     * @param int $pageNumber
     * @return Response
     * @throws QueryException
     */
    public function liste(Request $request,
        SortieRepository $sortieRepository,
        UserRepository $userRepository,
        SessionInterface $session,
        int $pageNumber = 1): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $maxResults = $session->get("maxResult");
        if(!isset($maxResults)){
            $session->set("maxResult", 12);
        }

        if($request->getMethod() === 'POST'){
            $data = $request->get('ideaPerPage');
        }
        if(isset($data) && $data > 0) {
            $session->set("maxResult", $data);
        }
        $maxResults = $session->get("maxResult");

        $participantConnecte = $userRepository->findOneBy(
            ["username" => $this->getUser()->getUsername()]
        )->getParticipant();
        $this->siteID = $participantConnecte->getEstRattacheA()->getId();
        $sorties = $sortieRepository->findSortiesParSite($this->siteID, $maxResults, $pageNumber);
        list($nbPage, $pagesAafficher) = $this->getInfosPourPagination($sortieRepository, $maxResults, $pageNumber, $session);

        $dataFromSession = $session->get('data');


        $form = $this->createForm(SortieFiltreType::class);
        $form->handleRequest($request);
        if (($form->isSubmitted() && $form->isValid()) || isset($dataFromSession)) {
            $data = $form->getData();
            if(!isset($data))
            {
                $data = $dataFromSession;
            } else
            {
                $pageNumber = 1;
            }
            $session->set('data', $data);
            $txtRecherche = isset($data["nom_recherche"]);
            $dateDebut = isset($data["dateDebut"]);
            $dateFin = isset($data["dateFin"]);
            $estOrganisateur = $data["estOrganisateur"];
            $estInscrit = $data["estInscrit"];
            $estPasInscrit = $data["estPasInscrit"];
            $estSortiePassee = $data["estSortiePassee"];
            $dateJour = null;
            $this->siteID = $data["site"]->getId();
            if($txtRecherche || $dateDebut || $dateFin || $estOrganisateur || $estInscrit || $estPasInscrit || $estSortiePassee)
            {
                $sorties = [];

                $sorties = $this->GestionFiltres(
                    $txtRecherche,
                    $data,
                    $sortieRepository,
                    $sorties,
                    $dateDebut,
                    $dateFin,
                    $estOrganisateur,
                    $estInscrit,
                    $estPasInscrit,
                    $userRepository,
                    $estSortiePassee,
                    $maxResults,
                    $pageNumber,
                    $session
                );

            } else
            {

                    $sorties = [];
                    $sortiesConcernees = $sortieRepository->findSortiesParSite($this->siteID, $maxResults, $pageNumber);
                    $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
                    $session->set('nbSorties', count($sorties));
                    $firstResult = ($pageNumber - 1) * $maxResults;
                    $sorties = array_slice($sorties, $firstResult, $maxResults);

            }
            list($nbPage, $pagesAafficher) = $this->getInfosPourPagination($sortieRepository, $maxResults, $pageNumber, $session);
        }

        $sorties = $this->triSortiesParDate($sorties);
        $session->set('sorties', $sorties);
        if ($pageNumber <= $nbPage && $pageNumber > 0) {
            return $this->render(
                'sortie/liste.html.twig',
                [
                    'sorties' => $sorties,
                    'ideaCountPage' => $nbPage,
                    'pageNumber' => $pageNumber,
                    'pagesToDisplay' => $pagesAafficher,
                    'firstEllipsis' => $pageNumber-2,
                    'secondEllipsis' => $pageNumber+3,
                    'maxResults' => $maxResults,
                    'form' => $form->createView(),
                    'title' => "Liste des sorties"
                ]
            );
        }

        return $this->redirectToRoute('page_sortie', ['pageNumber' => 1]);
    }

    /**
     * @param array $sortiesConcernees
     * @param array $sorties
     * @return array
     */
    public function ajoutUniqueAuTableau(array $sortiesConcernees, array $sorties): array
    {
        foreach ($sortiesConcernees as $sortie) {
            if (!in_array($sortie, $sorties) && $sortie->getSite()->getId() == $this->siteID) {
                $sorties[] = $sortie;
            }
        }

        return $sorties;
    }

    /**
     * @Route("/creer/sortie", name="page_creer_sortie")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SiteRepository $siteRepository
     * @param ParticipantRepository $participantRepository
     * @param EtatRepository $etatRepository
     * @param UserRepository $userRepository
     * @return Response
     */
    public function createSortie(Request $request,
                                 EntityManagerInterface $em,
                                 SiteRepository $siteRepository,
                                 ParticipantRepository $participantRepository,
                                 EtatRepository $etatRepository,
                                 UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie);

        $sortieForm->handleRequest($request);
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            if ($sortieForm->get('enregistrer')->isClicked()) {
                $sortie->setEtat($etatRepository->findOneBy(["libelle" => "Créée"]));
            }
            if ($sortieForm->get('publier')->isClicked()) {
                $sortie->setEtat($etatRepository->findOneBy(["libelle" => "Ouverte"]));
            }
            $user = $userRepository->findOneBy(["username" => $this->getUser()->getUsername()]);
            $site = $siteRepository->findOneBy(["id" => $user->getParticipant()->getEstRattacheA()]);

            $sortie->setSite($site);
            $sortie->setOrganisateur($user->getParticipant());
            $em->persist($sortie);
            $em->flush();
            $this->addFlash("success", "Sortie créée avec succès !");

            return $this->redirectToRoute('page_sortie');
        }

        return $this->render('sortie/formulaire.html.twig', [
            "sortieForm" => $sortieForm->createView(),
            "title" => "Créer une sortie"
        ]);
    }

    /**
     * @Route("/sortie/detail/{id}", name="page_details_sortie", requirements={"id": "\d+"})
     * @param SortieRepository $sortieRepository
     * @param int|null $id
     * @return Response
     */
    public function detailSortie(SortieRepository $sortieRepository, int $id = null): Response
    {
        $sortie = $sortieRepository->find($id);
        $nom = $sortie->getNom();
        $lieu = $sortie->getLieu();

        $dateHeure = $sortie->getDateHeureDebut()->getTimestamp();
        $dateLimiteInscription = $sortie->getDateLimiteInscription()->getTimestamp();
        $latitude = ($lieu->getLatitude()) ? : '-';
        $longitude = ($lieu->getLongitude() ? : '-');

        return $this->render('sortie/details.html.twig', [
                "sortie" => $sortie,
                'title' => 'Details de la sortie',
                'nom' => $nom,
                'lieu' => $lieu,
                'dateHeure' => date('d/m/Y', $dateHeure) . ' à ' . date('H:m', $dateHeure),
                'dateLimite' => date('d/m/Y', $dateLimiteInscription),
                'latitude' => $latitude,
                'longitude' => $longitude,

            ]
        );
    }

    /**
     * @param bool $txtRecherche
     * @param $data
     * @param SortieRepository $sortieRepository
     * @param array $sorties
     * @param bool $dateDebut
     * @param bool $dateFin
     * @param $estOrganisateur
     * @param $estInscrit
     * @param $estPasInscrit
     * @param UserRepository $userRepository
     * @param $estSortiePassee
     * @param $maxResults
     * @param $pageNumber
     * @param SessionInterface $session
     * @return array
     * @throws QueryException
     */
    public function GestionFiltres(
        bool $txtRecherche,
        $data,
        SortieRepository $sortieRepository,
        array $sorties,
        bool $dateDebut,
        bool $dateFin,
        $estOrganisateur,
        $estInscrit,
        $estPasInscrit,
        UserRepository $userRepository,
        $estSortiePassee,
        $maxResults,
        $pageNumber,
        SessionInterface $session
    ): array {
        if ($txtRecherche) {
            $texte = $data["nom_recherche"];
            $sortiesConcernees = $sortieRepository->findSortiesParTexte($texte);
            $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
        }

        if ($dateDebut && $dateFin) {
            $dateD = $data["dateDebut"];
            $dateF = $data["dateFin"];

            $sortiesConcernees = $sortieRepository->findSortiesEntreDeuxDates($dateD, $dateF);
            $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
        } elseif ($dateDebut) {
            $dateD = $data["dateDebut"];
            $sortiesConcernees = $sortieRepository->findSortiesApresUneDate($dateD);
            $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
        } elseif ($dateFin) {
            $dateF = $data["dateFin"];
            $sortiesConcernees = $sortieRepository->findSortiesAvantUneDate($dateF);
            $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
        }

        if ($estOrganisateur || $estInscrit || $estPasInscrit) {
            $participantConnecte = $userRepository->findOneBy(
                ["username" => $this->getUser()->getUsername()]
            )->getParticipant();
            $participantID = $participantConnecte->getID();
            if ($estOrganisateur) {
                $sortiesConcernees = $sortieRepository->findSortiesByOrganisateur($participantID);
                $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
            }
            if ($estInscrit) {
                $sortiesConcernees = $sortieRepository->findSortiesByParticipant($participantID);
                $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
            }
            if ($estPasInscrit) {
                $sortiesConcernees = $sortieRepository->findSortiesByParticipantPasInscrit($participantID);
                $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
            }
        }

        if ($estSortiePassee) {
            $dateJour = new DateTime('NOW');
            $date = date_format($dateJour, "Y-m-d G:i:s");
            $sortiesConcernees = $sortieRepository->findSortiesParDatePassee($date);
            $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
        }

        $session->set('nbSorties', count($sorties));
        $firstResult = ($pageNumber - 1) * $maxResults;
        $sorties = array_slice($sorties, $firstResult, $maxResults);

        return $sorties;
    }

    /**
     * @param array $sorties
     * @return array
     */
    public function triSortiesParDate(array $sorties): array
    {
        usort(
            $sorties,
            function ($a, $b) {
                $ad = $a->getDateHeureDebut();
                $bd = $b->getDateHeureDebut();

                if ($ad == $bd) {
                    return 0;
                }

                return $ad > $bd ? -1 : 1;
            }
        );

        return $sorties;
    }

    /**
     * @param SortieRepository $sortieRepository
     * @param int $maxResults
     * @param int $pageNumber
     * @param SessionInterface $session
     * @return array
     */
    public function getInfosPourPagination(SortieRepository $sortieRepository,
        int $maxResults,
        int $pageNumber,
        SessionInterface $session): array
    {
        $nbSortiesSession = $session->get('nbSorties');
        if(isset($nbSortiesSession)) {
            $nbSorties = $nbSortiesSession;
            $session->remove('nbSorties');
        } else {
            $nbSorties = $sortieRepository->countSorties();
        }

        $nbPage = ceil($nbSorties / $maxResults);
        $pagesAafficher = array($pageNumber - 1, $pageNumber + 1, $pageNumber + 2);
        if($nbPage == 0)
            $nbPage = 1;
        return array($nbPage, $pagesAafficher);
    }

}
