<?php

namespace Oro\Bundle\ChannelBundle\Validator;

use Symfony\Component\Validator\Constraint;

class ChannelCustomerIdentityConstraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
