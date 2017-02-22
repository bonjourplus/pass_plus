<?php

namespace PassPlusBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StateType extends AbstractType
{
    //Form used for creating statuses
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name')
            ->add('description')
            ->add('color', ChoiceType::class, array(
                'choices'=>
                    ['Blue' => 'blue',
                    'Red' => 'red',
                    'Purple'=>'purple',
                    'Indigo'=>'indigo',
                    'Teal'=>'teal',
                    'Green'=>'green',
                    'Amber'=>'amber',
                    'Deep-orange'=>'deep-orange',
                    'Brown'=>'brown',
                    'Grey'=>'grey'],
                'choice_attr' => function($val, $key, $index) {
                    return ['class'=> 'btn-flat '.$val,
                            'style'=>'display : block'];
                }
            ))
            ->add('activated', ChoiceType::class, array(
                'choices' =>
                    ['Activé' => 1,
                    'Désactivé' => 0],
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'PassPlusBundle\Entity\State'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'passplusbundle_state';
    }


}
