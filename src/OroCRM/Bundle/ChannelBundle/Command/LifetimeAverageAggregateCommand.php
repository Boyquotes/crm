<?php

namespace OroCRM\Bundle\ChannelBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use OroCRM\Bundle\ChannelBundle\Async\Topics;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LifetimeAverageAggregateCommand extends Command implements CronCommandInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '0 4 * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('oro:cron:lifetime-average:aggregate');
        $this->setDescription('Run daily aggregation of average lifetime value per channel');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'This option enforces regeneration of aggregation values from scratch(Useful after system timezone change)'
        );
        $this->addOption(
            'use-delete',
            null,
            InputOption::VALUE_NONE,
            'This option enforces to use DELETE statement instead TRUNCATE for force mode'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $message = new Message();
        $message->setBody([
            'force' => (bool) $input->getOption('force'),
            'clear_table_use_delete' => (bool) $input->getOption('use-delete'),
        ]);
        $message->setPriority(MessagePriority::VERY_LOW);

        $this->getMessageProducer()->send(Topics::AGGREGATE_LIFETIME_AVERAGE, $message);

        $output->writeln('<info>Completed!</info>');
    }

    /**
     * @return MessageProducerInterface
     */
    private function getMessageProducer()
    {
        return $this->container->get('oro_message_queue.message_producer');
    }
}
