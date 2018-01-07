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
                $this->contentVariables = $event->params['variables'];
var_dump($this->contentVariables);
exit;
                // convert EmailModel to our Mandrill_MessageModel
                $this->message->convertFromEmailModel($this->emailModel);

                // render content parts
                $this->renderHtmlContent();

                // actually send message to mandrill
                $result = $this->mandrill->messages->send($this->message->getAttributes());

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

        // render html body
        $this->message->html = craft()->templates->renderString($this->message->html, $this->contentVariables);

        // don't let Twig use the HTML escaping strategy on the plain text portion body of the email.
        craft()->templates->getTwig()->getExtension('escaper')->setDefaultStrategy(false);
        $this->message->text = craft()->templates->renderString($this->message->text, $this->contentVariables);
        craft()->templates->getTwig()->getExtension('escaper')->setDefaultStrategy('html');

        // return to the original template mode
        $templatesService->setTemplateMode($oldTemplateMode);
    }
}
