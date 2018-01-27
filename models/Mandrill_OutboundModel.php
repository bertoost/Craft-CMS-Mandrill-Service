<?php

namespace Craft;

/**
 * Class Mandrill_OutboundModel
 */
class Mandrill_OutboundModel extends BaseModel
{
    protected $elementType = MandrillModel::ElementTypeOutbound;

    /**
     * @return string
     */
    function __toString()
    {
        return $this->messageId;
    }

    /**
     * {@inheritdoc}
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'id'        => AttributeType::Number,
            'messageId' => AttributeType::String,
            'sender'    => AttributeType::String,
            'subject'   => AttributeType::String,
            'to'        => AttributeType::String,
            'opens'     => AttributeType::Number,
            'clicks'    => AttributeType::Number,
            'state'     => AttributeType::String,
        ]);
    }
}