<?php

namespace Craft;

/**
 * Class AbstractMandrillService
 */
abstract class AbstractMandrillService extends BaseApplicationComponent
{
    /**
     * @var BaseModel
     */
    public $config;

    /**
     * @var \Mandrill
     */
    public $mandrill;

    /**
     * @var UserModel
     */
    public $user;

    /**
     * @var array
     */
    public $contentVariables = [];

    /**
     * @var EmailModel
     */
    public $emailModel;

    /**
     * @var Mandrill_MessageModel
     */
    public $message;

    /**
     * @var DateTime|null
     */
    public $sentAt;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->config = craft()->plugins->getPlugin('mandrill')->getSettings();

        $apiKey = $this->config->apiKey;
        if (null !== ($configApiKey = craft()->config->get('mandrillApiKey'))) {
            $apiKey = $configApiKey;
        }

        if (!empty($apiKey)) {

            $this->mandrill = new \Mandrill($apiKey);

            $this->initMessage();
        }
    }

    /**
     * Initializes message. For internal purposes only
     */
    private function initMessage()
    {
        $this->emailModel = new EmailModel();
        $this->message = new Mandrill_MessageModel();
    }

    /**
     * Override the initialized EmailModel
     *
     * @param EmailModel $emailModel
     *
     * @return $this
     */
    public function setEmailModel(EmailModel $emailModel)
    {
        $this->emailModel = $emailModel;

        return $this;
    }

    /**
     * @param array $contentVariables
     *
     * @return AbstractMandrillService
     */
    public function setContentVariables(array $contentVariables)
    {
        $this->contentVariables = $contentVariables;

        return $this;
    }

    /**
     * @return boolean
     */
    public function send()
    {
        $event = new Event($this, [
            'user'         => $this->user,
            'emailModel'   => $this->emailModel,
            'variables'    => $this->contentVariables,
            '_mandrill'    => true,
            'emailMessage' => $this->message,
        ]);

        craft()->email->onBeforeSendEmail($event);

        if ($event->performAction) {

            try {
                // in case a plugin changed any variables in onBeforeSendEmail
                $this->contentVariables = $event->params['variables'];
                $this->contentVariables['user'] = $this->user;

                // convert EmailModel to our Mandrill_MessageModel
                $this->message->convertFromEmailModel($this->emailModel);

                // render content parts
                $this->renderHtmlContent();

                // actually send message to mandrill
                $sentAt = $this->getSentAt($event->params);
                $result = $this->mandrill->messages->send($this->message->getAttributes(), false, null, $sentAt);

                // capture errors
                if (in_array($result[0]['status'], ['rejected', 'invalid'])) {
                    $errorMsg = sprintf('Mandrill says: %s', $result[0]['status']);
                    if (!empty($result[0]['reject_reason'])) {
                        $errorMsg .= ' Reject reason: ' . $result[0]['reject_reason'];
                    }

                    throw new \Exception($errorMsg);
                }

                // fire an 'onSendEmail' event
                craft()->email->onSendEmail(new Event($this, [
                    'user'         => $this->user,
                    'emailModel'   => $this->emailModel,
                    'variables'    => $this->contentVariables,
                    '_mandrill'    => true,
                    'emailMessage' => $this->message,
                    'result'       => $result,
                ]));

                // reset message
                $this->initMessage();

                return true;

            } catch (\Exception $e) {

                // fire an 'onSendEmailError' event
                craft()->email->onSendEmailError(new Event($this, [
                    'user'         => $this->user,
                    'emailModel'   => $this->emailModel,
                    'variables'    => $this->contentVariables,
                    'error'        => $e->getMessage(),
                    '_mandrill'    => true,
                    'emailMessage' => $this->message,
                ]));

                Craft::log('Email error: ' . $e->getMessage(), LogLevel::Error);
            }
        }

        return false;
    }

    /**
     * @throws Exception
     * @throws \Twig_Error_Runtime
     */
    public function renderHtmlContent()
    {
        $templatesService = craft()->templates;
        $oldTemplateMode = $templatesService->getTemplateMode();

        // run parser only when nog coming from Craft's messages service
        // since the message service is already parsing the html body
        if (!isset($this->contentVariables['emailKey'])) {

            if (craft()->getEdition() >= Craft::Client) {

                // is there a custom HTML template set?
                $settings = craft()->email->getSettings();
                if (!empty($settings['template'])) {

                    $templatesService->setTemplateMode(TemplateMode::Site);
                    $template = $settings['template'];
                }
            }

            if (empty($template)) {

                // default to the _special/email.html template
                $templatesService->setTemplateMode(TemplateMode::CP);
                $template = '_special/email';
            }

            $this->message->html = "{% extends '{$template}' %}\n" .
                "{% set body %}\n" .
                $this->message->html .
                "{% endset %}\n";
        }

        // render html body
        $this->message->html = craft()->templates->renderString($this->message->html, $this->contentVariables);

        // don't let Twig use the HTML escaping strategy on the plain text portion body of the email.
        craft()->templates->getTwig()->getExtension('escaper')->setDefaultStrategy(false);
        $this->message->text = craft()->templates->renderString($this->message->text, $this->contentVariables);
        craft()->templates->getTwig()->getExtension('escaper')->setDefaultStrategy('html');

        // return to the original template mode
        $templatesService->setTemplateMode($oldTemplateMode);
    }

    /**
     * @param DateTime|null $dateTime
     *
     * @return $this
     */
    public function setSentAt(DateTime $dateTime = null)
    {
        $this->sentAt = $dateTime;

        return $this;
    }

    /**
     * @param array $eventParams
     *
     * @return DateTime|null
     */
    public function getSentAt(array $eventParams = [])
    {
        if (empty($this->sentAt) || !($this->sentAt instanceof DateTime)) {

            $this->sentAt = (isset($eventParams['mandrillSentAt'])) ? $eventParams['mandrillSentAt']->format('Y-m-d H:i:s') : null;
        }

        return $this->sentAt;
    }
}
