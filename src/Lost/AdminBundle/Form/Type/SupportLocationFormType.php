<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SupportLocationFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('name', null, array('label' => 'Support Location', 'required' => true))
                ->add('code', null, array('label' => 'Location Short Code', 'required' => true));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Lost\UserBundle\Entity\SupportLocation',
        ));
    }

    public function getName() {
        return 'lost_user_support_location';
    }

}
