<?php
namespace OroCRM\Bundle\AnalyticsBundle\Tests\Functional\Service;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessagePriority;
use OroCRM\Bundle\AnalyticsBundle\Async\Topics;
use OroCRM\Bundle\AnalyticsBundle\Model\RFMAwareInterface;
use OroCRM\Bundle\AnalyticsBundle\Service\ScheduleCalculateAnalyticsService;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\ChannelBundle\Tests\Functional\Fixture\LoadChannel;

/**
 * @dbIsolationPerTest
 */
class ScheduleCalculateAnalyticsServiceTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadChannel::class]);
        $this->getMessageProducer()->clear();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $service = $this->getContainer()->get('orocrm_analytics.schedule_calculate_analytics');

        $this->assertInstanceOf(ScheduleCalculateAnalyticsService::class, $service);
    }

    public function testShouldScheduleAnalyticsCalculateIfStatusTrueAndRFMEnabled()
    {
        // test for code written on modern yaml programming language

        /** @var Channel $channel */
        $channel = $this->getReference('default_channel');
        $channel->setStatus(false);

        $this->getEntityManager()->persist($channel);
        $this->getEntityManager()->flush();

        $channel->setStatus(true);
        $channel->setData([RFMAwareInterface::RFM_STATE_KEY => true]);

        $this->getMessageProducer()->clear();

        $this->getEntityManager()->persist($channel);
        $this->getEntityManager()->flush();

        $traces = $this->getMessageProducer()->getTopicSentMessages(Topics::CALCULATE_CHANNEL_ANALYTICS);
        self::assertCount(1, $traces);
        self::assertEquals([
            'channel_id' => $channel->getId(),
            'customer_ids' => [],
        ], $traces[0]['message']->getBody());
        self::assertEquals(MessagePriority::VERY_LOW, $traces[0]['message']->getPriority());
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return self::getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return MessageCollector
     */
    private function getMessageProducer()
    {
        return self::getContainer()->get('oro_message_queue.message_producer');
    }
}
