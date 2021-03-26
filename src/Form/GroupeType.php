<?php

namespace App\Form;

use App\Entity\Groupe;
use App\Entity\Participant;
use App\Entity\Site;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
//inversedby
class GroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('libelle')
            ->add("participants", EntityType::class, [
                "label" => "Participant(s)",
                "class" => Participant::class,
                "required" => false,
                'multiple' => true,
                "query_builder" => function(EntityRepository $er) {
                    return $er->createQueryBuilder("p")->orderBy("p.prenom, p.nom", "ASC");
                },
            ])
            ->add(
                'Enregistrer',
                SubmitType::class,
                [
                    'label' => 'Enregistrer',
                    'attr' => [
                        'class' => 'btn btn-primary',
                    ],
                ]
            )
            ->add("Supprimer",
            SubmitType::class,
            [
                "label" => "Supprimer",
                'attr' => [
                    'class' => 'btn btn-danger',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Groupe::class,
        ]);
    }
}
