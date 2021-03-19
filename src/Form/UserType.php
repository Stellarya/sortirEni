<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                "label" => "Pseudo",
            ])
            ->add('password', PasswordType::class, [
                "label" => "Mot de passe",
            ])
            ->add("old_password", PasswordType::class, [
                "label" => "Ancien mot de passe",
                "mapped" => false,
                'required' => false,
            ])
            ->add("new_password", RepeatedType::class, [
                "mapped" => false,
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => false,
                'first_options'  => ['help' => 'New password'],
                'second_options' => ['help' => 'Repeat new password'],
            ])
            ->add('email', EmailType::class, [
                "label" => "E-mail",
            ])
            ->add('roles', ChoiceType::class, [
                "choices" => [
                    "ROLE_USER" => true,
                    "ROLE_ADMIN" => false,
                ]
            ])
            ->add("avatar", FileType::class, [
                "label" => "Avatar",
                "mapped" => false,
                "required"  => false,
                'constraints' => [
                    new File([
                        'maxSize' => '3M',
                        'mimeTypes' =>[
                            'image/jpeg',
                            'image/pjpeg',
                            'image/png',
                            'image/x-png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Image non conforme',
                    ])
                ],
            ])
            //->add('participant')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
