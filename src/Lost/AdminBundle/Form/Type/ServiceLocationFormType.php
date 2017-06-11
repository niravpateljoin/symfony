<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Lost\UserBundle\Repository\CountryRepository;
use Lost\AdminBundle\Entity\ServiceLocation;
use Lost\AdminBundle\Entity\IpAddressZone;

class ServiceLocationFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('name', null, array('label' => 'name', 'required' => true))
                ->add('description', null, array('label' => 'description', 'required' => true))
                ->add('country','entity',array(
                        'class'=>'LostUserBundle:Country',
                        'property'      => 'name',
                        'query_builder' => function(CountryRepository $er) {
                            return $er->createQueryBuilder('c')
                                ->addSelect('(CASE c.name WHEN \'UNITED KINGDOM\' THEN 2 WHEN \'UNITED STATES\' THEN 1 ELSE 3 END) AS HIDDEN ORD')
                                ->add('orderBy','ORD ASC')
                                ->addOrderBy('c.name','ASC');
                        },
                        'required'=>true))
                ->add('ipAddressZones', 'collection', array(
                                'type' => new IpAddressZoneFormType(),
                                'allow_add' => true,
                                'allow_delete' => true,
                                'prototype' => true,
                                'by_reference' => false,
                        ));        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\AdminBundle\Entity\ServiceLocation',
        ));
    }

    public function getName() {
        return 'lost_service_location';
    }

}
