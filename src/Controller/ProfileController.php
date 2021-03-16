<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\User;
use App\Form\EditProfileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profile/edit", name="profile_edit")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function index(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");
        $form = $this->createForm(EditProfileType::class);
        $participantForm = $form->get("participant");
        $participantForm->remove("actif");
        $participantForm->remove("estRattacheA");
        $participantForm->remove("estInscrit");
        $userForm = $form->get("user");
        $userForm->remove("roles");
        $userForm->remove("password");

        /** @var User $user */
        $user = $this->getUser();
        /** @var Participant $participant */
        $participant = $user->getParticipant();
        $participantForm->setData($participant);
        $userForm->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->getDoctrine()->getConnection()->beginTransaction();
                try {
                    $old_passwd = $userForm["old_password"]->getData();
                    if (isset($old_passwd)) {
                        if (!$passwordEncoder->isPasswordValid($user, $old_passwd)) {
                            throw new \Exception("L'ancien mot de passe saisi ne correspond pas à celui attendu.");
                        }
                        $user->setPassword($passwordEncoder->encodePassword($user, $userForm["new_password"]->getData()));
                    }
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($participant);
                    $em->persist($user);
                    $em->flush();
                    $this->getDoctrine()->getConnection()->commit();
                    $this->addFlash("success", "Profil édité avec succès !");
                } catch (\Exception $e) {
                    $this->getDoctrine()->getConnection()->rollback();
                    $this->addFlash("alert", $e->getMessage());
                }
            }
        }

        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'form' => $form->createView(),
            'title' => 'Mon profil'
        ]);
    }
}
