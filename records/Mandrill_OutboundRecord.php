<?php

namespace Craft;

/**
 * Class Mandrill_OutboundRecord
 */
class Mandrill_OutboundRecord extends BaseRecord
{
    /**
     * {@inheritdoc}
     */
    public function getTableName()
    {
        return 'mandrill_outbound';
    }

    /**
     * {@inheritdoc}
     */
    public function defineAttributes()
    {
        return [
            'messageId' => [AttributeType::String, 'required' => true],
            'sender'    => [AttributeType::String, 'required' => true],
            'subject'   => [AttributeType::String, 'required' => true],
            'to'        => [AttributeType::String, 'required' => true],
            'opens'     => [AttributeType::Number, 'required' => true, 'default' => 0],
            'clicks'    => [AttributeType::Number, 'required' => true, 'default' => 0],
            'state'     => [AttributeType::String, 'required' => true, 'default' => 'sent'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function defineRelations()
    {
        return [
            'element' => [static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE],
        ];
    }
}