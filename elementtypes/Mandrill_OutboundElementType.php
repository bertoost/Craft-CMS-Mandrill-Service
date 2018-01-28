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
            '*' => [
                'label'       => Craft::t('All outbound'),
                'defaultSort' => ['messageTs', 'desc'],
            ],
        ];

        $stateTotals = craft()->mandrill_outbound->getTotalStates();
        if (array_sum($stateTotals) > 0) {

            $sources = array_merge($sources, [
                'filter-state' => [
                    'heading' => Craft::t('State filter'),
                ],
            ]);

            foreach ($stateTotals as $stateName => $stateTotal) {
                if ($stateTotal > 0) {

                    $nested = [];
                    if ($stateName === Mandrill_OutboundModel::STATE_REJECTED) {

                        $rejectedTotals = craft()->mandrill_outbound->getRejectedTotals();
                        foreach ($rejectedTotals as $rejectName => $rejectedTotal) {
                            if ($rejectedTotal > 0) {
                                $label = str_replace(['-', '_'], ' ', $rejectName);
                                $nested[$rejectName] = [
                                    'label'       => Craft::t(ucfirst($label)),
                                    'criteria'    => ['rejectReason' => $rejectName],
                                    'defaultSort' => ['messageTs', 'desc'],
                                ];
                            }
                        }
                    }

                    $sources = array_merge($sources, [
                        $stateName => [
                            'label'       => Craft::t(ucfirst($stateName) . ' messages'),
                            'criteria'    => ['state' => $stateName],
                            'nested'      => $nested,
                            'defaultSort' => ['messageTs', 'desc'],
                        ],
                    ]);
                }
            }
        }

        return $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function defineSortableAttributes()
    {
        $attributes = [
            'to'        => Craft::t('To'),
            'subject'   => Craft::t('Subject'),
            'messageTs' => Craft::t('Sent at'),
        ];

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTableAttributes($source = null)
    {
        $attributes = [
            'to',
            'subject',
            'opens',
            'clicks',
            'state',
            'messageTs',
        ];

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function defineCriteriaAttributes()
    {
        return [
            'to'           => AttributeType::String,
            'messageId'    => AttributeType::String,
            'sate'         => AttributeType::String,
            'rejectReason' => AttributeType::String,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function defineAvailableTableAttributes()
    {
        $attributes = [
            'to'           => ['label' => Craft::t('To')],
            'sender'       => ['label' => Craft::t('Sender')],
            'subject'      => ['label' => Craft::t('Subject')],
            'opens'        => ['label' => Craft::t('Opens')],
            'clicks'       => ['label' => Craft::t('Clicks')],
            'state'        => ['label' => Craft::t('State')],
            'rejectReason' => ['label' => Craft::t('Reject reason')],
            'messageTs'    => ['label' => Craft::t('Sent at')],
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
                           outbound.messageTs,
                           outbound.sender,
                           outbound.subject,
                           outbound.to,
                           outbound.opens,
                           outbound.clicks,
                           outbound.state,
                           outbound.rejectReason');
        $query->join('mandrill_outbound outbound', 'outbound.id = elements.id');

        if (!empty($criteria->search)) {
            $query->andWhere(DbHelper::parseParam('outbound.to', $criteria->search, $query->params));
        }

        if (!empty($criteria->messageId)) {
            $query->andWhere(DbHelper::parseParam('outbound.messageId', $criteria->messageId, $query->params));
        }

        if (!empty($criteria->state)) {
            $query->andWhere(DbHelper::parseParam('outbound.state', $criteria->state, $query->params));
        }

        if (!empty($criteria->rejectReason)) {
            $query->andWhere(DbHelper::parseParam('outbound.rejectReason', $criteria->rejectReason, $query->params));
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
     * @param Mandrill_OutboundModel|BaseElementModel $element
     * @param string $attribute
     *
     * @return mixed|string
     */
    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        switch ($attribute) {
            case 'state':
                $state = $element->state;

                if ($state === Mandrill_OutboundModel::STATE_REJECTED) {
                    $state = [$state, $element->rejectReason];
                }

                return craft()->templates->render('mandrill/_formats/state', [
                    'state' => $state,
                ]);
                break;

            case 'messageTs':
                $dateTime = new DateTime();
                $dateTime->setTimestamp($element->messageTs);

                return craft()->templates->render('mandrill/_formats/datetime', [
                    'datetime' => $dateTime,
                ]);
                break;
        }

        return parent::getTableAttributeHtml($element, $attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function populateElementModel($row)
    {
        return Mandrill_OutboundModel::populateModel($row);
    }
}