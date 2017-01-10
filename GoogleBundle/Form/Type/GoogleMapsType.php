<?php

namespace Application\Sonata\GoogleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GoogleMapsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('country', 'country', [])
            ->add('postCode')
            ->add('city')
            ->add('street')
            ->add('no')
            ->add($options['latitude_field'], 'number', $options['latitude_field_options'])
            ->add($options['longitude_field'], 'number', $options['longitude_field_options'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['latitude_field'] = $options['latitude_field'];
        $view->vars['longitude_field'] = $options['longitude_field'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'inherit_data' => true,
            'latitude_field' => 'latitude',
            'longitude_field' => 'longitude',
            'longitude_field_options' => [],
            'latitude_field_options' => [],
        ));

        $resolver->setRequired(array(
            'latitude_field',
            'longitude_field'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'google_maps_type';
    }
}