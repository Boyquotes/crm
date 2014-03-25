<?php

namespace OroCRM\Bundle\MagentoBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

use OroCRM\Bundle\MagentoBundle\Utils\WSIUtils;
use OroCRM\Bundle\MagentoBundle\Provider\Transport\SoapTransport;
use OroCRM\Bundle\MagentoBundle\Provider\Transport\MagentoTransportInterface;

class CartExpirationProcessor
{
    use LoggerAwareTrait;

    const DEFAULT_PAGE_SIZE = 200;

    /** @var TypesRegistry */
    protected $registry;

    /** @var EntityManager */
    protected $em;

    /** @var MagentoTransportInterface */
    protected $transport;

    /** @var array */
    protected $stores;

    public function __construct(ServiceLink $registryLink, EntityManager $em)
    {
        $this->registryLink = $registryLink;
        $this->em           = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Channel $channel)
    {
        $this->configure($channel);

        $qb = $this->em->getRepository('OroCRMMagentoBundle:Cart')->createQueryBuilder('c')
            ->select('c.id')
            ->where('c.channel = :channel')
            ->setParameter('channel', $channel);

        $result = new BufferedQueryResultIterator($qb);

        $ids   = [];
        $count = 0;
        foreach ($result as $id) {
            $ids[] = $id;
            $count++;

            if (0 === $count % self::DEFAULT_PAGE_SIZE) {

            }
        }

        if (!empty($ids)) {

        }
        $filterBag = new BatchFilterBag();
        $filterBag->addStoreFilter($stores);
        $filters          = $filterBag->getAppliedFilters();
        $filters['pager'] = ['page' => 1, 'pageSize' => self::DEFAULT_PAGE_SIZE];

        $result    = $transport->call(SoapTransport::ACTION_ORO_CART_LIST, $filters);
        $result    = WSIUtils::processCollectionResponse($result);
        $resultIds = array_map(
            function (&$item) {
                return (int)$item->entity_id;
            },
            $result
        );
        var_dump($resultIds);
    }

    protected function processBatch($ids) {

    }

    /**
     * Configure processor
     *
     * @param Channel $channel
     *
     * @throws \LogicException
     */
    protected function configure(Channel $channel)
    {
        /** @var MagentoTransportInterface $transport */
        $transport = clone $this->registryLink->getService()
            ->getTransportTypeBySettingEntity($channel->getTransport(), $channel->getType());
        $transport->init($channel->getTransport());
        $settings = $channel->getTransport()->getSettingsBag();

        if (!$this->transport->isExtensionInstalled()) {
            throw new \LogicException('Could not retrieve carts via SOAP with out installed Oro Bridge module');
        }

        $websiteId = $settings->get('website_id');

        $stores        = [];
        $magentoStores = iterator_to_array($transport->getStores());
        foreach ($magentoStores as $store) {
            if ($store['website_id'] == $websiteId) {
                $stores[] = $store['store_id'];
            }
        }

        if (empty($stores)) {
            throw new \LogicException(sprintf('Could not resolve store dependency for website id: %d', $websiteId));
        }

        $this->transport = $transport;
        $this->stores    = $stores;
    }
}
