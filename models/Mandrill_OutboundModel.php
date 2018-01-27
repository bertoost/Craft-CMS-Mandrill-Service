<?php

namespace Craft;

/**
 * Class Mandrill_OutboundModel
 */
class Mandrill_OutboundModel extends BaseElementModel
{
    // states
    const STATE_SENT = 'sent';
    const STATE_QUEUED = 'queued';
    const STATE_SCHEDULED = 'scheduled';
    const STATE_BOUNCED = 'bounced';
    const STATE_REJECTED = 'rejected';
    const STATE_INVALID = 'invalid';

    // main reject reasons
    const REJECTED_HARD_BOUNCE = 'hard-bounce';
    const REJECTED_SOFT_BOUNCE = 'soft-bounce';
    const REJECTED_SPAM = 'spam';
    const REJECTED_UNSUBSCRIBE = 'unsub';
    // others
    const REJECTED_CUSTOM = 'custom';
    const REJECTED_INVALID_SENDER = 'invalid-sender';
    const REJECTED_INVALID = 'invalid';
    const REJECTED_TEST_LIMIT = 'test-mode-limit';
    const REJECTED_UNSIGNED = 'unsigned';
    const REJECTED_RULE = 'rule';

    protected $elementType = MandrillModel::ElementTypeOutbound;

    /**
     * @return string
     */
    function __toString()
    {
        return $this->to;
    }

    /**
     * {@inheritdoc}
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'id'           => AttributeType::Number,
            'messageId'    => AttributeType::String,
            'messageTs'    => AttributeType::Number,
            'sender'       => AttributeType::String,
            'subject'      => AttributeType::String,
            'to'           => AttributeType::String,
            'opens'        => AttributeType::Number,
            'clicks'       => AttributeType::Number,
            'state'        => AttributeType::String,
            'rejectReason' => AttributeType::String,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('mandrill/details/' . $this->id);
    }

    /**
     * Converts a result item from Mandrill to our model
     *
     * @param array $item
     */
    public function convertFromItem(array $item = [])
    {
        $this->messageTs = $item['ts'];
        $this->subject = $item['subject'];
        $this->sender = $item['sender'];
        $this->to = $item['email'];
        $this->opens = $item['opens'];
        $this->clicks = $item['clicks'];

        $this->state = self::STATE_SENT;
        switch ($item['state']) {
            case 'rejected':
                $this->state = self::STATE_REJECTED;
                break;
            case 'bounched':
                $this->state = self::STATE_BOUNCED;
                break;
        }
    }
}