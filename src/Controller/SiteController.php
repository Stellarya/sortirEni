<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\SiteType;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/sites", name="sites")
 */
class SiteController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     * @param SiteRepository $siteRepository
     * @return Response
     */
    public function list(SiteRepository $siteRepository)
    {
        $toSite = $siteRepository->findAll();

        return $this->render('sites/listeSite.html.twig', [
            'title' => 'Liste des Sites',
            'sites' => $toSite
        ]);
    }

    /**
     * @Route("/form/{id}", name="_form", requirements={"id": "-?\d+"})
     * @param Request $request
     * @param int $id
     * @param EntityManagerInterface $em
     * @param SiteRepository $siteRepository
     * @return Response
     */
    public function form(Request $request, int $id,
                         EntityManagerInterface $em,
                         SiteRepository $siteRepository): Response
    {
        if (-1 == $id) {
            $oSite = new Site();
            $title = 'Créer un site';
        } else {
            $oSite = $siteRepository->find($id);
            $title = 'Modifier un site';
        }

        $siteForm = $this->createForm(SiteType::class, $oSite);

        $siteForm->handleRequest($request);
        if ($siteForm->isSubmitted() && $siteForm->isValid()) {
            $em->persist($oSite);
            $em->flush();
            if (-1 == $id) {
                $this->addFlash("success", "Site créée avec succès !");
            } else {
                $this->addFlash("success", "Site modifiée avec succès !");
            }

            return $this->redirectToRoute('sites_list');
        }

        return $this->render('sites/formulaire.html.twig', [
            "siteForm" => $siteForm->createView(),
            "title" => $title
        ]);
    }

    /**
     * @Route("/delete/{id<\d+>?0}", name="_delete")
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $oSite = $em->find(Site::class, $id);

            $em->remove($oSite);
            $em->flush();
            return new JsonResponse([
                "is_ok" => true,
                "message" => "Site supprimé avec succès"
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                "is_ok" => false,
                "message" => "Le site ne peux pas être supprimé",
            ]);
        }
    }
}