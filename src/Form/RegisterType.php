<?php

namespace App\Form;

use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add("username", TextType::class, ["mapped"=>false])
            ->add("password", PasswordType::class, ["mapped"=>false])
            ->add("email", EmailType::class, ["mapped"=>false])


            ->add("estRattacheA", EntityType::class, [
                "class" => Site::class,
                "query_builder" => function(EntityRepository $er) {
                    return $er->createQueryBuilder("s")->orderBy("s.nom", "ASC");
                },
                "choice_label" => "nom",
            ])
            ->add("nom")
            ->add("prenom")
            ->add("telephone")


        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
