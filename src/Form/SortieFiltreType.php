<?php

namespace App\Form;

use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieFiltreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('site',
                EntityType::class,
                [
                    'class' => Site::class,
                    'choice_label' => 'nom'
                ])
            ->add('nom_recherche',
                TextType::class,
                [
                    'label' => 'Le nom de la sortie contient : '
                ])
            ->add('dateDebut',
                DateType::class,
                [
                    'label' => 'Entre '
                ])
            ->add('dateFin',
                DateType::class,
                [
                    'label' => 'et '
                ])
            ->add('estOrganisateur',
                CheckboxType::class,
                [
                    'label' => 'Sorties dont je suis l\'organisateur/trice'
                ])
            ->add('estInscrit',
                CheckboxType::class,
                [
                    'label' => 'Sorties auxquelles je suis inscrit/e'
                ])
            ->add('estPasInscrit',
                CheckboxType::class,
                [
                    'label' => 'Sorties auxquelles je ne suis pas inscrit/e'
                ])
            ->add('estSortiePassee',
                CheckboxType::class,
                [
                    'label' => 'Sorties passées'
                ])
            ->add(
                'rechercher',
                SubmitType::class,
                [
                    'label' => 'Rechercher',
                    'attr' => [
                        'class' => 'btn btn-primary',
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
