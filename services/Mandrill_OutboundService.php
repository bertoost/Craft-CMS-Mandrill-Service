<?php

namespace Craft;

/**
 * Class Mandrill_OutboundService
 */
class Mandrill_OutboundService extends AbstractMandrillService
{
    /**
     * Returns a criteria model for Mandrill_OutboundModel elements.
     *
     * @param array $attributes
     *
     * @throws Exception
     * @return ElementCriteriaModel
     */
    public function getCriteria(array $attributes = [])
    {
        return craft()->elements->getCriteria(MandrillModel::ElementTypeOutbound, $attributes);
    }

    /**
     *
     * Get an outbound element model by id
     *
     * @param int $id
     *
     * @return Mandrill_OutboundModel|null
     * @throws Exception
     */
    public function getById($id)
    {
        return $this->getCriteria(['limit' => 1, 'id' => $id])->first();
    }

    /**
     *
     * Get an outbound element model by id
     *
     * @param int $messageId
     *
     * @return Mandrill_OutboundModel|null
     * @throws Exception
     */
    public function getByMessageId($messageId)
    {
        return $this->getCriteria(['limit' => 1, 'messageId' => $messageId])->first();
    }

    /**
     * Returns a list of states with the number of found records
     *
     * @return array
     * @throws Exception
     */
    public function getTotalStates()
    {
        $totals = [
            Mandrill_OutboundModel::STATE_SENT      => 0,
            Mandrill_OutboundModel::STATE_SCHEDULED => 0,
            Mandrill_OutboundModel::STATE_QUEUED    => 0,
            Mandrill_OutboundModel::STATE_BOUNCED   => 0,
            Mandrill_OutboundModel::STATE_REJECTED  => 0,
            Mandrill_OutboundModel::STATE_INVALID   => 0,
        ];

        foreach ($totals as $stateName => &$total) {
            $total = $this->getCriteria(['state' => $stateName])->count();
        }

        return $totals;
    }

    /**
     * Returns a list of states with the number of found records
     *
     * @return array
     * @throws Exception
     */
    public function getRejectedTotals()
    {
        $totals = [
            Mandrill_OutboundModel::REJECTED_HARD_BOUNCE    => 0,
            Mandrill_OutboundModel::REJECTED_SOFT_BOUNCE    => 0,
            Mandrill_OutboundModel::REJECTED_SPAM           => 0,
            Mandrill_OutboundModel::REJECTED_UNSUBSCRIBE    => 0,
            Mandrill_OutboundModel::REJECTED_CUSTOM         => 0,
            Mandrill_OutboundModel::REJECTED_INVALID_SENDER => 0,
            Mandrill_OutboundModel::REJECTED_INVALID        => 0,
            Mandrill_OutboundModel::REJECTED_TEST_LIMIT     => 0,
            Mandrill_OutboundModel::REJECTED_UNSIGNED       => 0,
            Mandrill_OutboundModel::REJECTED_RULE           => 0,
        ];

        foreach ($totals as $rejectName => &$total) {
            $total = $this->getCriteria(['rejectReason' => $rejectName])->count();
        }

        return $totals;
    }

    /**
     * Saves the outbound model into the database
     *
     * @param Mandrill_OutboundModel $model
     *
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function save(Mandrill_OutboundModel $model)
    {
        $isNew = !$model->id;

        // get the invoice record
        if ($model->id) {
            $record = Mandrill_OutboundRecord::model()->findById($model->id);
            if (!$record) {
                throw new \Exception(Craft::t('No model exists with the ID "{id}".', ['id' => $model->id]));
            }
        } else {
            $record = new Mandrill_OutboundRecord();
        }

        // copy attributes to record
        $record->setAttributes($model->getAttributes(), false);

        // Validate the attributes
        $record->validate();
        $model->addErrors($record->getErrors());

        if (!$model->hasErrors()) {

            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            try {
                // save the element!
                if (craft()->elements->saveElement($model)) {

                    // now that we have an element ID, save it on the other stuff
                    if ($isNew) {
                        $record->id = $model->id;
                    }

                    // save the invoice!
                    $record->save(false); // Skip validation now

                    if ($transaction !== null) {
                        $transaction->commit();
                    }

                    return true;
                }
            } catch (\Exception $e) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }
                throw $e;
            }
        }

        return false;
    }

    /**
     * @param string $messageId
     * @return array
     */
    public function getMandrillMessageInfo($messageId)
    {
        return $this->mandrill->messages->info($messageId);
    }

    /**
     * @param string $messageId
     * @return array
     */
    public function getMandrillMessageContent($messageId)
    {
        return $this->mandrill->messages->content($messageId);
    }

    /**
     * @return array
     */
    public function getSyncList()
    {
        $date = new DateTime();
        $date->modify('-4 days');

        return $this->mandrill->messages->search('*', $date->format('Y-m-d'));
    }
}