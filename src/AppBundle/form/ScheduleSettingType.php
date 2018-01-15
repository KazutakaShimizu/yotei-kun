<?php

namespace AppBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;


class ScheduleSettingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dayFrom', DateType::class, array(
                'label' => '開始日',
                'required'  => false,
            ))
            ->add('dayTo', DateType::class, array(
                'label' => '終了日',
                'required'  => false,
            ))
            ->add('timeFrom', TimeType::class, array(
                'label' => '開始時間',
                'required'  => false,
            ))
            ->add('timeTo', TimeType::class, array(
                'label' => '終了時間',
                'required'  => false,
            ))
            ->add('minimumUnit', "integer", array(
                'label' => '最低時間',
                'required'  => false,
                "attr" => array(
                    'min'  => 0,
                    'max'  => 150,
                    "step" => 15,
                ),            
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ScheduleSetting'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'yoteikun_appbundle_schedule_setting';
    }

}