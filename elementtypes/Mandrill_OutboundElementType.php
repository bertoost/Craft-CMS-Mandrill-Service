<?php

namespace Craft;

/**
 * Class Mandrill_OutboundElementType
 */
class Mandrill_OutboundElementType extends BaseElementType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Craft::t('Mandrill Outbound');
    }

    /**
     * {@inheritdoc}
     */
    public function hasContent()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSources($context = null)
    {
        $sources = [
            '*'        => [
                'label' => Craft::t('All outbound'),
            ],
//            '---'      => [
//                'heading' => Craft::t('Filter invoices'),
//            ],
        ];

        return $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTableAttributes($source = null)
    {
        $attributes = [
            'sender',
            'subject',
            'to',
            'opens',
            'clicks',
            'state',
        ];

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function defineCriteriaAttributes()
    {
        return [
            'to' => AttributeType::String,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function defineAvailableTableAttributes()
    {
        $attributes = [
            'messageId' => ['label' => Craft::t('Message Id')],
            'sender'    => ['label' => Craft::t('Sender')],
            'subject'   => ['label' => Craft::t('Subject')],
            'to'        => ['label' => Craft::t('To')],
            'opens'     => ['label' => Craft::t('Opens')],
            'clicks'    => ['label' => Craft::t('Clicks')],
            'state'     => ['label' => Craft::t('State')],
        ];

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query->addSelect('outbound.id,
                           outbound.messageId,
                           outbound.sender,
                           outbound.subject,
                           outbound.to,
                           outbound.opens,
                           outbound.clicks,
                           outbound.state');
        $query->join('mandrill_outbound outbound', 'outbound.id = elements.id');

        if (!empty($criteria->search)) {
            $query->andWhere(DbHelper::parseParam('outbound.to', $criteria->search, $query->params));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function defineSearchableAttributes()
    {
        return [
            'to',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function populateElementModel($row)
    {
        return Mandrill_OutboundModel::populateModel($row);
    }
}