<?php

namespace Craft;

/**
 * Class MandrillController
 */
class MandrillController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        craft()->templates->includeCssResource('mandrill/css/style.css');
    }

    /**
     * @throws HttpException
     */
    public function actionIndex()
    {
        $variables = [
            'elementType' => MandrillModel::ElementTypeOutbound,
        ];

        $this->renderTemplate('mandrill/outbound/index', $variables);
    }

    /**
     * @param array $variables
     * @throws HttpException
     */
    public function actionDetails(array $variables = [])
    {
        $messageId = (isset($variables['messageId'])) ? $variables['messageId'] : null;
        if (empty($messageId)) {
            craft()->userSession->setError(Craft::t(sprintf('No message with ID "%d" found.', $messageId)));
            $this->redirect('mandrill');
        }

        $outboundModel = craft()->mandrill_outbound->getById($messageId);
        if (empty($outboundModel) || !($outboundModel instanceof Mandrill_OutboundModel)) {
            craft()->userSession->setError(Craft::t(sprintf('No record with ID "%d" found.', $messageId)));
            $this->redirect('mandrill');
        }
        $variables['outboundModel'] = $outboundModel;

        $message = craft()->mandrill_outbound->getMandrillMessageInfo($outboundModel->messageId);
        $variables['message'] = $message;

        // only valid items have details
        if (!in_array($outboundModel->state, [
            Mandrill_OutboundModel::STATE_REJECTED,
            Mandrill_OutboundModel::STATE_INVALID,
        ])) {

            $messageContent = craft()->mandrill_outbound->getMandrillMessageContent($outboundModel->messageId);
            $variables['messageContent'] = $messageContent;
        }

        $this->renderTemplate('mandrill/outbound/details', $variables);
    }

    /**
     * @param array $variables
     * @return void
     */
    public function actionDetailsHtmlView(array $variables = [])
    {
        $messageId = (isset($variables['messageId'])) ? $variables['messageId'] : null;
        if (empty($messageId)) {
            craft()->userSession->setError(Craft::t(sprintf('No message with ID "%d" found.', $messageId)));
            $this->redirect('mandrill');
        }

        $outboundModel = craft()->mandrill_outbound->getById($messageId);
        if (empty($outboundModel) || !($outboundModel instanceof Mandrill_OutboundModel)) {
            craft()->userSession->setError(Craft::t(sprintf('No record with ID "%d" found.', $messageId)));
            $this->redirect('mandrill');
        }
        $variables['outboundModel'] = $outboundModel;

        // only valid items have details
        if (!in_array($outboundModel->state, [
            Mandrill_OutboundModel::STATE_REJECTED,
            Mandrill_OutboundModel::STATE_INVALID,
        ])) {

            $messageContent = craft()->mandrill_outbound->getMandrillMessageContent($outboundModel->messageId);
            if (isset($messageContent['html']) && !empty($messageContent['html'])) {

                echo $messageContent['html'];
                craft()->end();
            }
        }
    }
}