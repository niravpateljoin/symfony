<?php

namespace Lost\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SettingFormType extends AbstractType {
    
    protected $isMaintenance;

    public function __construct ($isMaintenance = false) {
        
        $this->isMaintenance = $isMaintenance;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('name');
        if ($this->isMaintenance) {
            
            $builder->add('value', 'choice', array(
                'choices' => array('True' => 'True', 'False' => 'False'),
            ));
        } else {
            
            $builder->add('value');
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {

        $resolver->setDefaults(array(
            'data_class' => 'Lost\AdminBundle\Entity\Setting'
        ));
    }

    /**
     * @return string
     */
    public function getName() {

        return 'lost_admin_setting';
    }

}
