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
            'sender'    => [AttributeType::String, 'required' => false],
            'subject'   => [AttributeType::String, 'required' => false],
            'to'        => [AttributeType::String, 'required' => true],
            'opens'     => [AttributeType::Number, 'required' => false, 'default' => 0],
            'clicks'    => [AttributeType::Number, 'required' => false, 'default' => 0],
            'state'     => [AttributeType::String, 'required' => false, 'default' => 'sent'],
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