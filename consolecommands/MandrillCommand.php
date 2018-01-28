<?php

namespace Craft;

/**
 * Class MandrillCommand
 */
class MandrillCommand extends BaseCommand
{
    /**
     * Syncs the outbound list with local track-list
     */
    public function actionSyncOutbound()
    {
        $list = craft()->mandrill_outbound->getSyncList();
        foreach ($list as $item) {

            $outboundModel = craft()->mandrill_outbound->getByMessageId($item['_id']);
            if (empty($outboundModel) || !($outboundModel instanceof Mandrill_OutboundModel)) {

                $outboundModel = new Mandrill_OutboundModel();
                $outboundModel->messageId = $item['_id'];
            }

            $outboundModel->convertFromItem($item);

            if (!craft()->mandrill_outbound->save($outboundModel)) {
                Craft::log(sprintf('Failed to save Mandrill Outbound record for %s', $outboundModel->to), LogLevel::Error);
            }
        }
    }
}