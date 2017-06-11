<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('name', null, array('required' => true));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\UserBundle\Entity\Group',
        ));
    }

    public function getName() {
        return 'lost_admin_group';
    }

}
