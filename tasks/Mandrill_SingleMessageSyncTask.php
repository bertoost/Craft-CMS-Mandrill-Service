<?php

namespace Craft;

/**
 * Class Mandrill_SingleMessageSyncTask
 */
class Mandrill_SingleMessageSyncTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return Craft::t('Retrieves a single Mandrill message info');
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalSteps()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function runStep($step)
    {
        // sleep two seconds, since this can be to fast
        // a message is not jet indexed at Mandrill
        sleep(2);

        $outboundModel = new Mandrill_OutboundModel();
        $outboundModel->messageId = $this->getSettings()->messageId;
        $outboundModel->syncWithMandrill();

        craft()->mandrill_outbound->save($outboundModel);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defineSettings()
    {
        return [
            'messageId' => AttributeType::String,
        ];
    }
}