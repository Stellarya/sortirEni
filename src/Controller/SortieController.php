<?php

namespace App\Controller;

use App\Entity\Participant;
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
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Mobile_Detect;

class SortieController extends AbstractController
{
    public $siteID = null;
    public const ETAT_COULEURS = [
        'Ouverte' => '#038E59',
        'Clôturée' => '#0B0404',
        'Créée' => '#ECC330',
        'Activité en cours' => '#FF8029',
        'Passée' => '#283671',
        'Annulée' => '#B83026',
        'Activité terminée' => '#283671',
        'Activité historisée' => '#E866A6',
    ];


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
        $detect = new Mobile_Detect();

        $maxResults = $session->get("maxResult");
        if (!isset($maxResults)) {
            $session->set("maxResult", 8);
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
                    'title' => "Liste des sorties",
                    'couleurs' => self::ETAT_COULEURS,
                    'isMobile' => $detect->isMobile(),
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
        if (count($sorties) == 0)
        {
            $sorties = $sortiesConcernees;
        } else
        {
            $sortiesTemp = [];
            foreach ($sortiesConcernees as $sortie) {
                if (in_array($sortie, $sorties) && $sortie->getSite()->getId() == $this->siteID) {
                    $sortiesTemp[] = $sortie;
                }
            }
            $sorties = $sortiesTemp;
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
            $participant = $userRepository->findOneBy(["username" => $this->getUser()->getUsername()])->getParticipant();

            if(!$this->peutModifier($participant, $sortie))
            {
                $this->addFlash("alert", "Erreur ! Vous n'avez pas les droits pour faire cette action.");
                return $this->redirectToRoute('page_sortie');
            }
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
     * @param UserRepository $userRepository
     * @param int|null $id
     * @param int $pageNumber
     * @return Response
     */
    public function detailSortie(SortieRepository $sortieRepository,
                                 UserRepository $userRepository,
                                 int $id = null,
                                 int $pageNumber = 1): Response
    {
        $sortie = $sortieRepository->find($id);

        $nom = $sortie->getNom();
        $lieu = $sortie->getLieu();
        $user = $userRepository->findOneBy(["username" => $this->getUser()->getUsername()])->getParticipant();

        $droits = $this->droits($user, $sortie);

        $dateHeure = $sortie->getDateHeureDebut()->getTimestamp();
        $dateLimiteInscription = $sortie->getDateLimiteInscription()->getTimestamp();
        $latitude = ($lieu->getLatitude()) ?: '-';
        $longitude = ($lieu->getLongitude() ?: '-');

        $toParticipants = $sortie->getParticipants();
        $tUserParticipant = array();
        foreach ($toParticipants as $oParticipant){
            $userParticipant = $userRepository->findOneBy(["participant" => $oParticipant->getId()]);
            $tUserParticipant[$oParticipant->getId()] = $userParticipant->getId();
        }

        return $this->render('sortie/details.html.twig', [
                "sortie" => $sortie,
                'title' => 'Details de la sortie',
                'nom' => $nom,
                'lieu' => $lieu,
                'dateHeure' => date('d/m/Y', $dateHeure) . ' à ' . date('H:m', $dateHeure),
                'dateLimite' => date('d/m/Y', $dateLimiteInscription),
                'latitude' => $latitude,
                'userActuel' => $user,
                'tUserParticipant' => $tUserParticipant,
                'longitude' => $longitude,
                'participants' => $toParticipants,
                'pageNumber' => $pageNumber,
                'peutSinscrire' => $droits['peutSinscrire'],
                'peutSeDesinscrire' => $droits['peutSeDesinscrire'],
                'peutModifier' => $droits['peutModifier'],
                'peutPublier' => $droits['peutPublier'],
                'peutAnnuler' => $droits['peutAnnuler']
            ]
        );
    }

    /**
     * @param Participant $user
     * @param Sortie $sortie
     * @return bool[]
     */
    public function droits (Participant $user, Sortie $sortie) : array
    {
        $utilisateurPresent = $this->estUtilisateurPresentDansParticipantsSortie($sortie, $user);
        $nomEtat = $sortie->getEtat()->getLibelle();

        $peutSinscrire = $this->peutSinscrire($nomEtat)  && !$utilisateurPresent;
        $peutSeDesinscrire = $this->peutSeDesinscrire($nomEtat) && $utilisateurPresent;
        $peutModifier = $this->peutModifier($user, $sortie);
        $peutPublier = $this->peutPublier($user, $sortie);
        $peutAnnuler = $this->peutAnnuler($user, $sortie);


        return array(
            'peutSinscrire' => $peutSinscrire,
            'peutSeDesinscrire' => $peutSeDesinscrire,
            'peutModifier' => $peutModifier,
            'peutPublier' => $peutPublier,
            'peutAnnuler' => $peutAnnuler,
        );
    }

    /**
     * @param Participant $user
     * @param Sortie $sortie
     * @return bool
     */
    public function peutModifier (Participant $user, Sortie $sortie) : bool
    {
        if($this->estAdmin())
            return true;
        if(!$this->estOrganisateur($user, $sortie))
            return false;

        $nomEtat = $sortie->getEtat()->getLibelle();
        if ($nomEtat != 'Créée')
        {
            return false;
        }
        return true;
    }

    /**
     * @param Participant $user
     * @param Sortie $sortie
     * @return bool
     */
    public function peutPublier (Participant $user, Sortie $sortie) : bool
    {
        $nomEtat = $sortie->getEtat()->getLibelle();
        if ($nomEtat != 'Créée')
        {
            return false;
        }
        if($this->estAdmin())
            return true;
        if(!$this->estOrganisateur($user, $sortie))
            return false;
        return true;
    }

    /**
     * @param Participant $user
     * @param Sortie $sortie
     * @return bool
     */
    public function peutAnnuler (Participant $user, Sortie $sortie) : bool
    {
        if($this->estAdmin())
            return true;
        if(!$this->estOrganisateur($user, $sortie))
            return false;

        $nomEtat = $sortie->getEtat()->getLibelle();
        if ($nomEtat != 'Ouverte' && $nomEtat != 'Clôturée' && $nomEtat != 'Créée')
        {
            return false;
        }
        return true;
    }

    /**
     * @param string $nomEtat
     * @return bool
     */
    public function peutSeDesinscrire(string $nomEtat) : bool
    {
        if ($nomEtat != 'Ouverte' && $nomEtat != 'Clôturée')
        {
                return false;
        }
        return true;
    }

    /**
     * @param string $nomEtat
     * @return bool
     */
    public function peutSinscrire(string $nomEtat) : bool
    {
        if ($nomEtat != 'Ouverte')
        {
            return false;
        }
        return true;
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
                $this->addFlash("success", "Inscription réussie.");
            } else {
                $this->addFlash("alert", "Une erreur s'est produite durant l'inscription.");
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
     * @param int $id
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
                $this->addFlash("success", "Désinscription réussie.");
            } else {
                $this->addFlash("alert", "Une erreur s'est produite durant la désinscription.");
                throw $this->createNotFoundException('Erreur ! Participant introuvable.');
            }
        }

        return $this->redirectToRoute('page_details_sortie', array('id' => $id));
    }

    /**
     * @Route("/sortie/annulation/{id}", name="page_annulation_sortie")
     * @param Request $request
     * @param SortieRepository $sortieRepository
     * @param EtatRepository $etatRepository
     * @param int $id
     * @return JsonResponse|RedirectResponse
     */
    public function annulationSortie(Request $request,
                                     SortieRepository $sortieRepository,
                                     EtatRepository $etatRepository,
                                     ParticipantRepository $participantRepository,
                                     int $id)
    {
        $oSortie = $sortieRepository->find($id);
        if ($request->getMethod() !== 'POST') {
            $this->addFlash("alert", "Erreur ! Vous n'avez pas les droits pour faire cette action.");
            return $this->redirectToRoute('page_sortie');
        }

        if ($oSortie) {
            $userID = $request->get('idParticipant');
            $participant = $participantRepository->find($userID);
            $messageAnnulation = $request->get('motifAnnulation');
            if($messageAnnulation == "")
            {
                $this->addFlash("alert", "Merci de saisir un message d'annulation.");
                return $this->redirectToRoute('page_details_sortie', array('id' => $id));
            }
            if(!$this->peutAnnuler($participant, $oSortie))
            {
                $this->addFlash("alert", "Erreur ! Vous n'avez pas les droits pour faire cette action.");
                return $this->redirectToRoute('page_sortie');
            }
            $etat = $etatRepository->findOneBy(["libelle" => "Annulée"]);
            $em = $this->getDoctrine()->getManager();
            $oSortie->setMessageAnnulation($messageAnnulation);
            $oSortie->setEtat($etat);
            $em->persist($oSortie);
            $em->flush();
            $this->addFlash("success", "Sortie annulée avec succès.");
            return $this->redirectToRoute('page_sortie');
        } else {
            return new JsonResponse([
                'errorMessage' => 'Erreur ! La sortie à supprimer n\'existe pas.'
            ]);
        }

    }

    /**
     * @Route("/sortie/publication/{id}", name="page_publication_sortie")
     * @param Request $request
     * @param SortieRepository $sortieRepository
     * @param ParticipantRepository $participantRepository
     * @param EtatRepository $etatRepository
     * @param int $id
     * @return RedirectResponse
     */
    public function publicationSortie(Request $request,
        SortieRepository $sortieRepository,
        ParticipantRepository $participantRepository,
        EtatRepository $etatRepository,
        int $id): RedirectResponse
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() === 'POST') {
            $idParticipant = $request->get('idParticipant');
            $oSortie = $sortieRepository->find($id);
            $oParticipant = $participantRepository->find($idParticipant);
            $etat = $etatRepository->findOneBy(["libelle" => "Ouverte"]);
            if ($oSortie && $oParticipant && $this->estOrganisateur($oParticipant, $oSortie)) {
                $oSortie->setEtat($etat);
                $em->persist($oSortie);
                $em->flush();
                $this->addFlash("success", "Sortie publiée avec succès !");
                return $this->redirectToRoute('page_sortie');
            } else {
                $this->addFlash("alert", "La sortie n'a pas pu être publiée !");
                throw $this->createNotFoundException('Erreur ! Participant introuvable.');
            }
        }

        return $this->redirectToRoute('page_details_sortie', array('id' => $id));
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
            $sortiesConcernees = $sortieRepository->findSortiesParDatePassee();
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

    /**
     * @param Sortie $sortie
     * @param Participant $user
     * @return bool
     */
    public function estUtilisateurPresentDansParticipantsSortie(Sortie $sortie, Participant $user): bool
    {
        $participants = $sortie->getParticipants();
        foreach ($participants as $participant) {
            if ($participant->getId() == $user->getId()) {
                return true;
            }

        }

        return false;
    }

    /**
     * @return bool
     */
    public function estAdmin(): bool
    {
        if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return true;
        }

        return false;
    }

    /**
     * @param Participant $user
     * @param Sortie $sortie
     * @return bool
     */
    public function estOrganisateur(Participant $user, Sortie $sortie): bool
    {
        if ($user->getId() != $sortie->getOrganisateur()->getId()) {
            return false;
        }
        return true;
    }

}
