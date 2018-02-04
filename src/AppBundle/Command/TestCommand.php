<?php
/**
 * Created by PhpStorm.
 * User: yasushi_sakita
 * Date: 2016/08/08
 * Time: 18:48
 */

namespace AppBundle\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\ScheduleSetting;
use AppBundle\Entity\User;
use Util\Helper;

class TestCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('yotei-kun:test')
            ->setDescription('test');
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $time = new \DateTime();
        $entity = new ScheduleSetting();
        $entity->setDayFrom($time);
        $entity->setDayTo($time->modify("+1 month"));
        $entity->setTimeFrom($time);
        $entity->setTimeTo($time->modify("+1 hour"));
        $entity->setMinimumUnit(60);
        // $entity = new User();
        // $entity->setTokenName("hoge");
        $em->persist($entity);
        $em->flush();
    }
}