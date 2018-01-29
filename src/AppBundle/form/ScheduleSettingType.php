<?php

namespace AppBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
            ->add('dayFrom', TextType::class, array(
                'label' => '開始日',
                'required'  => false,
            ))
            ->add('dayTo', TextType::class, array(
                'label' => '終了日',
                'required'  => false,
            ))
            ->add('timeFrom', TimeType::class, array(
                'widget' => 'single_text',
                'label' => '開始時間',
                'required'  => false,
            ))
            ->add('timeTo', TimeType::class, array(
                'widget' => 'single_text',
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
            ->add('interval', "integer", array(
                'label' => '前後何分あけるか',
                'required'  => false,
                "attr" => array(
                    'min'  => 0,
                    'max'  => 180,
                    "step" => 15,
                ),            
            ))
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                array($this, 'onPreSetData')
            );
        ;
    }

    public function onPreSetData(FormEvent $event){
        $postData = $event->getData();
        $postData["dayFrom"] = new \DateTime($postData["dayFrom"]);
        $tmp = new \DateTime($postData["dayTo"]);
        $postData["dayTo"] = $tmp->setTime(23, 59, 59);
        $event->setData($postData);
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