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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    public $siteID = null;
    /**
     * @Route("/sortie", name="page_sortie")
     * @param Request $request
     * @param SortieRepository $sortieRepository
     * @param UserRepository $userRepository
     * @return Response
     */
    public function liste(Request $request, SortieRepository $sortieRepository, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $sorties = $sortieRepository->findSortiesPubliees();
        $participantConnecte = $userRepository->findOneBy(["username" => $this->getUser()->getUsername()])->getParticipant();
        $this->siteID = $participantConnecte->getEstRattacheA()->getId();
        $form = $this->createForm(SortieFiltreType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {

            $data = $form->getData();
            $site = isset($data["site"]);
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

                if($txtRecherche)
                {
                    $texte = $data["nom_recherche"];
                    $sortiesConcernees = $sortieRepository->findSortiesParTexte($texte);
                    $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
                }

                if($dateDebut && $dateFin)
                {
                    $dateD = $data["dateDebut"];
                    $dateF = $data["dateFin"];
                    $sortiesConcernees = $sortieRepository->findSortiesEntreDeuxDates($dateD, $dateF);
                    $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
                } elseif ($dateDebut)
                {
                    $dateD = $data["dateDebut"];
                    $sortiesConcernees = $sortieRepository->findSortiesApresUneDate($dateD);
                    $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
                } elseif ($dateFin)
                {
                    $dateF = $data["dateFin"];
                    $sortiesConcernees = $sortieRepository->findSortiesAvantUneDate($dateF);
                    $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
                }

                if ($estOrganisateur || $estInscrit || $estPasInscrit)
                {
                    $participantConnecte = $userRepository->findOneBy(["username" => $this->getUser()->getUsername()]
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

                if($estSortiePassee)
                {
                    $dateJour = new DateTime('NOW');
                    $date = date_format($dateJour, "Y-m-d G:i:s");
                    $sortiesConcernees = $sortieRepository->findSortiesParDatePassee($date);
                    $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
                }

            } else
            {
                if($this->siteID == $data["site"]->getId()) {
                    $sorties = [];
                    $sortiesConcernees = $sortieRepository->findSortiesParSite($this->siteID);
                    $sorties = $this->ajoutUniqueAuTableau($sortiesConcernees, $sorties);
                }
            }

        }

        usort($sorties, function($a, $b) {
            $ad = $a->getDateHeureDebut();
            $bd = $b->getDateHeureDebut();

            if ($ad == $bd) {
                return 0;
            }

            return $ad < $bd ? -1 : 1;
        });

        return $this->render('sortie/liste.html.twig', ["sorties" => $sorties, "form" => $form->createView(),
                                    "title" => "Liste des sorties"
        ]);
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
     * @Route("/sortie/{id}", name="page_detail_sortie", requirements={"id": "\d+"})
     * @param SortieRepository $sortieRepository
     * @param int|null $id
     * @return Response
     */
    public function detailSortie(SortieRepository $sortieRepository, int $id = null): Response
    {
        $sortie = $sortieRepository->findOneBy(["id" => $id]);

        return $this->render('sortie/detail.html.twig', ["sortie" => $sortie]);
    }

    function array_sort($array, $on, $order=SORT_ASC)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }

}
