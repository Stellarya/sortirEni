<?php

namespace App\Form;

use App\Entity\Participant;
use App\Entity\Site;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                "label" => "Nom",
            ])
            ->add('prenom', TextType::class, [
                "label" => "Prénom",
            ])
            ->add('telephone', TextType::class, [
                "label" => "Téléphone",
            ])
            ->add('actif', CheckboxType::class, [
                "label" => "Actif",
                'required' => false,
            ])
            ->add("estRattacheA", EntityType::class, [
                "label" => "Site",
                "class" => Site::class,
                "query_builder" => function(EntityRepository $er) {
                    return $er->createQueryBuilder("s")->orderBy("s.nom", "ASC");
                },
                "choice_label" => "nom",
            ])
            //->add('estInscrit')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}
