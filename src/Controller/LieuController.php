<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LieuController
 * @package App\Controller
 * @Route("/lieux", name="lieux")
 */
class LieuController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     * @param LieuRepository $lieuRepository
     * @return Response
     */
    public function list(LieuRepository $lieuRepository)
    {
        $toLieu = $lieuRepository->findAll();

        return $this->render('lieux/listeLieu.html.twig', [
            'title' => 'Liste des Lieux',
            'lieux' => $toLieu
        ]);
    }

    /**
     * @Route("/form/{id}", name="_form", requirements={"id": "-?\d+"})
     * @param Request $request
     * @param int $id
     * @param EntityManagerInterface $em
     * @param LieuRepository $lieuRepository
     * @return Response
     */
    public function form(Request $request, int $id,
                         EntityManagerInterface $em,
                         LieuRepository $lieuRepository): Response
    {
        if (-1 == $id) {
            $oLieu = new Lieu();
            $title = 'Créer un site';
        } else {
            $oLieu = $lieuRepository->find($id);
            $title = 'Modifier un site';
        }

        $lieuForm = $this->createForm(LieuType::class, $oLieu);

        $lieuForm->handleRequest($request);
        if ($lieuForm->isSubmitted() && $lieuForm->isValid()) {
            $em->persist($oLieu);
            $em->flush();
            if (-1 == $id) {
                $this->addFlash("success", "Lieu créée avec succès !");
            } else {
                $this->addFlash("success", "Lieu modifiée avec succès !");
            }

            return $this->redirectToRoute('lieux_list');
        }

        return $this->render('lieux/formulaire.html.twig', [
            "lieuForm" => $lieuForm->createView(),
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
            $oLieu = $em->find(Lieu::class, $id);

            $em->remove($oLieu);
            $em->flush();
            return new JsonResponse([
                "is_ok" => true,
                "message" => "Lieu supprimé avec succès"
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                "is_ok" => false,
                "message" => "Le lieu ne peux pas être supprimé",
            ]);
        }
    }
}