<?php

namespace App\Controller;

use App\Form\SortieType;
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
     */
    public function createSortie(Request $request, EntityManagerInterface $em): Response
    {
        $sortie = new Sortie();
        $sortieForm = $this->createForm(SortieType::class, $sortie);

        $sortieForm->handleRequest($request);
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
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
