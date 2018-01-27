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
     * @return array
     */
    public function getSyncList()
    {
        $plugin = craft()->plugins->getPlugin('mandrill');
        $date = $plugin->getSettings()->lastSyncDate;

        return $this->mandrill->messages->search('*', $date->format('Y-m-d'));
    }
}