<?php

namespace OroCRM\Bundle\MagentoBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;

use OroCRM\Bundle\MagentoBundle\Provider\Iterator\UpdatedLoaderInterface;
use OroCRM\Bundle\MagentoBundle\Provider\Transport\MagentoTransportInterface;

class OrderConnector extends AbstractConnector implements MagentoConnectorInterface
{
    const CONNECTOR_TYPE = 'order';

    /** @var MagentoTransportInterface */
    protected $transport;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.magento.connector.order.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return self::ORDER_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return 'mage_order_import';
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $iterator = $this->transport->getOrders();

        // set start date and mode depending on status
        $status = $this->channel->getStatusesForConnector(self::CONNECTOR_TYPE, Status::STATUS_COMPLETED)->first();
        if ($iterator instanceof UpdatedLoaderInterface && false !== $status) {
            /** @var Status $status */
            $iterator->setMode(UpdatedLoaderInterface::IMPORT_MODE_UPDATE);
            $iterator->setStartDate($status->getDate());
        }

        return $iterator;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateConfiguration()
    {
        parent::validateConfiguration();

        if (!$this->transport instanceof MagentoTransportInterface) {
            throw new \LogicException('Option "transport" should implement "MagentoTransportInterface"');
        }
    }
}
