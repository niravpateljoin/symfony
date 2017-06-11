<?php

namespace Lost\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
//use Lost\UserBundle\Repository\CountryRepository;
use Lost\AdminBundle\Entity\Credit;
use Lost\UserBundle\Entity\User;
//use Lost\AdminBundle\Entity\IpAddressZone;

class CreditFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('credit', null, array('label' => 'credit', 'required' => true))
                ->add('amount', null, array('label' => 'amount', 'required' => true));
                  
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\AdminBundle\Entity\Credit',
        ));
    }

    public function getName() {
        return 'lost_credit';
    }

}
