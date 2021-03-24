<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Ville;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
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

        return $this->render(
            'lieux/listeLieu.html.twig',
            [
                'title' => 'Liste des Lieux',
                'lieux' => $toLieu,
            ]
        );
    }

    /**
     * @Route("/form/{id}", name="_form", requirements={"id": "-?\d+"})
     * @param Request $request
     * @param int $id
     * @param EntityManagerInterface $em
     * @param LieuRepository $lieuRepository
     * @param VilleRepository $villeRepository
     * @param Lieu|null $lieuFormSortie
     * @return Response
     */
    public function form(
        Request $request,
        int $id,
        EntityManagerInterface $em,
        LieuRepository $lieuRepository,
        VilleRepository $villeRepository,
        Lieu $lieuFormSortie = null
    ): Response {
        if (-1 == $id) {
            $oLieu = new Lieu();
            $title = 'Créer un lieu';
        } else {
            $oLieu = $lieuRepository->find($id);
            $title = 'Modifier un lieu';
        }

        $lieuForm = $this->createForm(LieuType::class, $oLieu);

        $lieuForm->handleRequest($request);
        if (isset($lieuFormSortie) || ($lieuForm->isSubmitted() && $lieuForm->isValid())) {

            if (isset($lieuFormSortie)) {
                $oLieu = $lieuFormSortie;
            }

            $nom = $oLieu->getNom();
            $rue = $oLieu->getRue();

            if (isset($nom) && isset($rue)) {
                $this->getLatitudeLongitude($oLieu);
            } else {
                $ville = new Ville();
                $this->getAdresseParLatitudeLongitude($oLieu, $ville);
                $villeExiste = $villeRepository->findOneBy(["nom" => $ville->getNom()]);
                if (!isset($villeExiste)) {
                    $oLieu->setVille($ville);
                    $em->persist($ville);
                } else {
                    $oLieu->setVille($villeExiste);
                }
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

        return $this->render(
            'lieux/formulaire.html.twig',
            [
                "lieuForm" => $lieuForm->createView(),
                "title" => $title,
            ]
        );
    }

    public function gestionCreationLieuParSortie(
        Request $request,
        int $id,
        EntityManagerInterface $em,
        LieuRepository $lieuRepository,
        FormInterface $form
    ) {

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

            return new JsonResponse(
                [
                    "is_ok" => true,
                    "message" => "Lieu supprimé avec succès",
                ]
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    "is_ok" => false,
                    "message" => "Le lieu ne peux pas être supprimé",
                ]
            );
        }
    }

    /**
     * @param Lieu|null $oLieu
     */
    public function getLatitudeLongitude(?Lieu $oLieu): void
    {
        $url = "http://api.positionstack.com/v1/forward?";
        $params = $oLieu->getRue()." ".$oLieu->getVille()->getCodePostal()." ".$oLieu->getVille()->getNom();
        $json = $this->getDataFromApi($params, $url, $oLieu);
        $oLieu->setLatitude($json["data"][0]["latitude"]);
        $oLieu->setLongitude($json["data"][0]["longitude"]);
    }

    /**
     * @param Lieu|null $oLieu
     * @param Ville $ville
     */
    public function getAdresseParLatitudeLongitude(?Lieu $oLieu, Ville $ville): void
    {
        $url = "http://api.positionstack.com/v1/reverse?";
        $params = $oLieu->getLatitude().",".$oLieu->getLongitude();
        $json = $this->getDataFromApi($params, $url, $oLieu);

        $oLieu->setRue($json["data"][0]["name"]);
        $ville->setNom($json["data"][0]["locality"]);
        foreach ($json as $dataArray) {
            foreach ($dataArray as $data) {
                if (isset($data["postal_code"])) {
                    $ville->setCodePostal($data["postal_code"]);
                    break;
                }
            }
        }

    }

    /**
     * @param string $params
     * @param string $url
     * @param Lieu|null $oLieu
     */
    public function getDataFromApi(string $params, string $url, ?Lieu $oLieu): array
    {
        $url = str_replace(' ', '%20', $url);
        $options = array(
            "access_key" => "ecbd4cc0a7f9d82bb659277126283c32",
            "query" => $params,
        );
        $url .= http_build_query($options, '', '&');

        try {
            $raw = file_get_contents($url);

            return json_decode($raw, true);

        } catch (\Exception $ex) {

        }
    }
}