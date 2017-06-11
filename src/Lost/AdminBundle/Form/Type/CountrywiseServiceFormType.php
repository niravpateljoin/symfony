<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Lost\UserBundle\Repository\CountryRepository;
use Lost\UserBundle\Repository\ServiceRepository;

class CountrywiseServiceFormType extends AbstractType {

    public function __construct($country = null, $service = null) {
//        $this->country = $country;
//        $this->service = $service;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        if(isset($options['data']) && $options['data']->getId() != '') {
            
//            $builder->add('country', 'entity', array('class' => 'LostUserBundle:Country','property' => 'name','data' => $this->country))
//                    ->add('services', 'entity', array('class' => 'LostUserBundle:Services','property' => 'name','data' => $this->service))
//                    ->add('status', 'choice', array('choices'  => array(1 => 'Active','' =>'Inactive'),'required' => false));
        } else {
        $builder->add('country', 'entity', array(
                    'class' => 'LostUserBundle:Country',
                    'property' => 'name',
                    'query_builder' => function(CountryRepository $er) {
                        return $er->createQueryBuilder('c')
                                ->addSelect('(CASE c.name WHEN \'UNITED KINGDOM\' THEN 2 WHEN \'UNITED STATES\' THEN 1 ELSE 3 END) AS HIDDEN ORD')
                                ->add('orderBy', 'ORD ASC')
                                ->addOrderBy('c.name', 'ASC');
                    },
                    'required' => true))
                ->add('services', 'entity', array(
                    'class' => 'LostUserBundle:Service',
                    'property' => 'name',
                    'multiple' => ($options['data']->getId()) ? false : true,
                    'query_builder' => function(ServiceRepository $er) {
                        return $er->createQueryBuilder('s')
                                ->where('s.status = :statusVal')
                                ->setParameter('statusVal', 1)
                                ->addOrderBy('s.name', 'ASC');
                    },
                    'required' => true));
        }
        $builder->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => false, 'empty_value' => 'Select Status'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\UserBundle\Entity\CountrywiseService'
        ));
    }

    public function getName() {
        return 'lost_countrywise_service_add';
    }

}
