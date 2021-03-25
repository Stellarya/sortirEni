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
            $title = 'Créer un lieu';
        } else {
            $oLieu = $lieuRepository->find($id);
            $title = 'Modifier un lieu';
        }

        $lieuForm = $this->createForm(LieuType::class, $oLieu);

        $lieuForm->handleRequest($request);
        if ($lieuForm->isSubmitted() && $lieuForm->isValid()) {
            // clé api ecbd4cc0a7f9d82bb659277126283c32
            $url = "http://api.positionstack.com/v1/forward?";
            //$url .= '&'."query=".$oLieu->getRue()." ".$oLieu->getVille()->getCodePostal()." ".$oLieu->getVille()->getNom();
            $url = str_replace(' ', '%20', $url);
            //dd($url);
            $params = $oLieu->getRue()." ".$oLieu->getVille()->getCodePostal()." ".$oLieu->getVille()->getNom();
            $options = array("access_key" => "ecbd4cc0a7f9d82bb659277126283c32",
                                "query" => $params);
            $url .= http_build_query($options, '', '&');

            try {
                $raw = file_get_contents($url);
                $json = json_decode($raw, true);
                $oLieu->setLatitude($json["data"][0]["latitude"]);
                $oLieu->setLongitude($json["data"][0]["longitude"]);
            } catch (\Exception $ex){

            }
            $em->persist($oLieu);
            $em->flush();
            if (-1 == $id) {
                $this->addFlash("success", "Lieu créé avec succès !");
            } else {
                $this->addFlash("success", "Lieu modifié avec succès !");
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