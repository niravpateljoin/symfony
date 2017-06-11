<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PermissionFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
        ->add('name', null, array('required' => true))
        ->add('code', null, array('required' => false, 'read_only' => true))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {

        $resolver->setDefaults(array(
            'data_class' => 'Lost\UserBundle\Entity\Permission'
        ));
    }

    public function getName() {

        return 'lost_admin_permission';
    }
}
