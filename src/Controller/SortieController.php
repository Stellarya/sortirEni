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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    /**
     * @Route("/sortie", name="page_sortie")
     * @param Request $request
     * @param SortieRepository $sortieRepository
     * @return Response
     */
    public function liste(Request $request, SortieRepository $sortieRepository): Response
    {
        $sorties = $sortieRepository->findAll();

        $form = $this->createForm(SortieFiltreType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

        }
        return $this->render('sortie/liste.html.twig', ["sorties" => $sorties, "form" => $form->createView(),
            "title" => "Liste des sorties"
        ]);
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
     * @Route("/sortie/{id}", name="page_details_sortie", requirements={"id": "\d+"})
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
}
