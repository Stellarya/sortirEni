<?php

namespace App\Form;

use App\Entity\Participant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("participant", ParticipantType::class, [
                //"class" => Participant::class,
                //"label" => "nom",
            ])
            ->add("user", UserType::class, [])
            ->add(
                'enregistrer',
                SubmitType::class,
                [
                    'label' => 'Enregistrer',
                    'attr' => [
                        'class' => 'btn btn-light',
                    ],
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
