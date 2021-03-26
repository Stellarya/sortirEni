<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/villes", name="villes")
 */
class VilleController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     * @param VilleRepository $villeRepository
     * @return Response
     */
    public function list(VilleRepository $villeRepository)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");

        $toVille = $villeRepository->findAll();

        return $this->render('villes/listeville.html.twig', [
            'title' => 'Liste des villes',
            'villes' => $toVille
        ]);
    }

    /**
     * @Route("/form/{id}", name="_form", requirements={"id": "-?\d+"})
     * @param Request $request
     * @param int $id
     * @param EntityManagerInterface $em
     * @param VilleRepository $villeRepository
     * @return Response
     */
    public function form(Request $request, int $id,
                         EntityManagerInterface $em,
                         VilleRepository $villeRepository): Response
    {
        if (-1 == $id) {
            $oVille = new Ville();
            $title = 'Créer une ville';
        } else {
            $oVille = $villeRepository->find($id);
            $title = 'Modifier une ville';
        }

        $villeForm = $this->createForm(VilleType::class, $oVille);

        $villeForm->handleRequest($request);
        if ($villeForm->isSubmitted() && $villeForm->isValid()) {
            $em->persist($oVille);
            $em->flush();
            if (-1 == $id) {
                $this->addFlash("success", "Ville créée avec succès !");
            } else {
                $this->addFlash("success", "Ville modifiée avec succès !");
            }

            return $this->redirectToRoute('villes_list');
        }

        return $this->render('villes/formulaire.html.twig', [
            "villeForm" => $villeForm->createView(),
            "title" => $title
        ]);
    }

    /**
     * @Route("/delete/{id<\d+>?0}", name="_delete")
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $oVille = $em->find(Ville::class, $id);

            $em->remove($oVille);
            $em->flush();
            return new JsonResponse([
                "is_ok" => true,
                "message" => "Ville supprimée avec succès"
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                "is_ok" => false,
                "message" => $e->getMessage(),
            ]);
        }
    }
}