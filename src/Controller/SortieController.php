<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    /**
     * @Route("/sortie", name="page_sortie")
     * @param SortieRepository $sortieRepository
     * @return Response
     */
    public function liste(SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->findAll();

        return $this->render('sortie/liste.html.twig', ["sortie" => $sortie]);
    }

    /**
     * @Route("/creer/sortie", name="page_creer_sortie")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SiteRepository $siteRepository
     * @param ParticipantRepository $participantRepository
     * @param EtatRepository $etatRepository
     * @return Response
     */
    public function createSortie(Request $request,
                                    EntityManagerInterface $em,
                                    SiteRepository $siteRepository,
                                    ParticipantRepository $participantRepository,
                                    EtatRepository $etatRepository): Response
    {
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie);

        $sortieForm->handleRequest($request);
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            if ($sortieForm->get('enregistrer')->isClicked())
            {
                $sortie->setEtat($etatRepository->findOneBy(["libelle" => "Créée"]));
            }
            if ($sortieForm->get('publier')->isClicked())
            {
                $sortie->setEtat($etatRepository->findOneBy(["libelle" => "Ouverte"]));
            }
            $participant = $participantRepository->findOneBy(["nom" => "Derrien"]);
            $site = $siteRepository->findOneBy(["id" => $participant->getEstRattacheA()]);

            $sortie->setSite($site);
            $sortie->setOrganisateur($participant);
            $em->persist($sortie);
            $em->flush();
            $this->addFlash("success", "Sortie créée avec succès !");

            return $this->redirectToRoute('home');
        }

        return $this->render('sortie/formulaire.html.twig', ["sortieForm" => $sortieForm->createView()]);
    }

    /**
     * @Route("/sortie/{id}", name="page_detail_sortie", requirements={"id": "\d+"})
     * @param SortieRepository $sortieRepository
     * @param int|null $id
     * @return Response
     */
    public function detailSortie(SortieRepository $sortieRepository, int $id = null) : Response
    {
        $sortie = $sortieRepository->findOneBy(["id" => $id]);

        return $this->render('sortie/detail.html.twig', ["sortie" => $sortie]);
    }
}
