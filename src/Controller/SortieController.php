<?php

namespace App\Controller;

use App\Entity\Sortie;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        if (!isset($maxResults)) {
            $session->set("maxResult", 12);
        }

        if ($request->getMethod() === 'POST') {
            $data = $request->get('ideaPerPage');
        }
        if (isset($data) && $data > 0) {
            $session->set("maxResult", $data);
        }
        $maxResults = $session->get("maxResult");

        $participantConnecte = $userRepository->findOneBy(
            ["username" => $this->getUser()->getUsername()]
        )->getParticipant();
        $this->siteID = $participantConnecte->getEstRattacheA()->getId();
        $sorties = $sortieRepository->findSortiesParSite($this->siteID);
        list($nbPage, $pagesAafficher) = $this->getInfosPourPagination(
            $sortieRepository,
            $maxResults,
            $pageNumber,
            $session
        );

        $dataFromSession = $session->get('data');

        $form = $this->createForm(SortieFiltreType::class);
        $form->handleRequest($request);
        if (($form->isSubmitted() && $form->isValid()) || isset($dataFromSession)) {
            $data = $form->getData();
            if (!isset($data)) {
                $data = $dataFromSession;
            } else {
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
            if ($txtRecherche || $dateDebut || $dateFin || $estOrganisateur || $estInscrit || $estPasInscrit || $estSortiePassee) {
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

            } else {
                $sorties = [];
                $sortiesConcernees = $sortieRepository->findSortiesParSite($this->siteID);
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
                    'firstEllipsis' => $pageNumber - 2,
                    'secondEllipsis' => $pageNumber + 3,
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
     * @Route("/sortie/{id}/formulaire", name="page_formulaire_sortie", requirements={"id": "-?\d+"})
     * @param Request $request
     * @param int $id
     * @param EntityManagerInterface $em
     * @param SortieRepository $sortieRepository
     * @param SiteRepository $siteRepository
     * @param EtatRepository $etatRepository
     * @param UserRepository $userRepository
     * @return Response
     */
    public function form(Request $request, int $id,
                         EntityManagerInterface $em,
                         SortieRepository $sortieRepository,
                         SiteRepository $siteRepository,
                         EtatRepository $etatRepository,
                         UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (-1 == $id) {
            $sortie = new Sortie();
            $title = 'Créer une Sortie';
        } else {
            $sortie = $sortieRepository->find($id);
            $title = 'Modifier une Sortie';
        }

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
            if (-1 == $id) {
                $this->addFlash("success", "Sortie créée avec succès !");
            } else {
                $this->addFlash("success", "Sortie modifiée avec succès !");
            }


            return $this->redirectToRoute('page_sortie');
        }

        return $this->render('sortie/formulaire.html.twig', [
            "sortieForm" => $sortieForm->createView(),
            "title" => $title
        ]);
    }

    /**
     * @Route("/sortie/detail/{id}/{pageNumber}", name="page_details_sortie", requirements={"id": "\d+"})
     * @param SortieRepository $sortieRepository
     * @param ParticipantRepository $participantRepository
     * @param int|null $id
     * @param int $pageNumber
     * @return Response
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function detailSortie(SortieRepository $sortieRepository,
        ParticipantRepository $participantRepository,
        int $id = null,
        int $pageNumber = 1): Response
    {
        $sortie = $sortieRepository->find($id);
        $nom = $sortie->getNom();
        $lieu = $sortie->getLieu();
        $participants = $participantRepository->findParticipantsBySortie($sortie);
        dump($participants);

        $dateHeure = $sortie->getDateHeureDebut()->getTimestamp();
        $dateLimiteInscription = $sortie->getDateLimiteInscription()->getTimestamp();
        $latitude = ($lieu->getLatitude()) ?: '-';
        $longitude = ($lieu->getLongitude() ?: '-');

        $tParticipants = $sortie->getParticipants();

        return $this->render('sortie/details.html.twig', [
                "sortie" => $sortie,
                'title' => 'Details de la sortie',
                'nom' => $nom,
                'lieu' => $lieu,
                'dateHeure' => date('d/m/Y', $dateHeure) . ' à ' . date('H:m', $dateHeure),
                'dateLimite' => date('d/m/Y', $dateLimiteInscription),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'participants' => $tParticipants,
                'tIdParticipants' => $participants,
                'pageNumber' => $pageNumber,
            ]
        );
    }

    /**
     * @Route("/sortie/inscription/{id}", name="page_inscription_sortie")
     * @param Request $request
     * @param SortieRepository $sortieRepository
     * @param ParticipantRepository $participantRepository
     * @param int $id
     * @return RedirectResponse
     */
    public function inscriptionSortie(Request $request,
                                      SortieRepository $sortieRepository,
                                      ParticipantRepository $participantRepository,
                                      int $id): RedirectResponse
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() === 'POST') {
            $idParticipant = $request->get('idParticipant');
            $oSortie = $sortieRepository->find($id);
            $oParticipant = $participantRepository->find($idParticipant);
            if ($oSortie && $oParticipant) {
                $oSortie->addParticipant($oParticipant);
                $em->persist($oSortie);
                $em->flush();
            } else {
                throw $this->createNotFoundException('Erreur ! Participant introuvable.');
            }
        }

        return $this->redirectToRoute('page_details_sortie', array('id' => $id));
    }

    /**
     * @Route("/sortie/desinscription/{id}", name="page_desinscription_sortie")
     * @param Request $request
     * @param SortieRepository $sortieRepository
     * @param ParticipantRepository $participantRepository
     * @return RedirectResponse
     */
    public function desinscriptionSortie(Request $request,
                                         SortieRepository $sortieRepository,
                                         ParticipantRepository $participantRepository,
                                         int $id): RedirectResponse
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() === 'POST') {
            $idParticipant = $request->get('idParticipant');
            $oSortie = $sortieRepository->find($id);
            $oParticipant = $participantRepository->find($idParticipant);
            if ($oSortie && $oParticipant) {
                $oSortie->removeParticipant($oParticipant);
                $em->persist($oSortie);
                $em->flush();
            } else {
                throw $this->createNotFoundException('Erreur ! Participant introuvable.');
            }
        }

        return $this->redirectToRoute('page_details_sortie', array('id' => $id));
    }

    /**
     * @Route("/sortie/annulation/{id}", name="page_annulation_sortie")
     * @param SortieRepository $sortieRepository
     * @param int $id
     * @return JsonResponse|RedirectResponse
     */
    public function annulationSortie(SortieRepository $sortieRepository,
                                     int $id)
    {
        $oSortie = $sortieRepository->find($id);
        if ($oSortie) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($oSortie);
            $em->flush();

            return $this->redirectToRoute('page_sortie');
        } else {
            return new JsonResponse([
                'errorMessage' => 'Erreur ! La sortie à supprimer n\'existe pas.'
            ]);
        }

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
    public function GestionFiltres(bool $txtRecherche,
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
                                   SessionInterface $session): array
    {
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
        if (isset($nbSortiesSession)) {
            $nbSorties = $nbSortiesSession;
            $session->remove('nbSorties');
        } else {
            $nbSorties = $sortieRepository->countSorties();
        }

        $nbPage = ceil($nbSorties / $maxResults);
        $pagesAafficher = array($pageNumber - 1, $pageNumber + 1, $pageNumber + 2);
        if ($nbPage == 0)
            $nbPage = 1;
        return array($nbPage, $pagesAafficher);
    }

}
