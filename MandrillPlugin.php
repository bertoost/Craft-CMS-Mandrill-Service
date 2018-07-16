<?php

namespace Craft;

/**
 * Class MandrillPlugin
 */
class MandrillPlugin extends BasePlugin
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Craft::t('Mandrill Service');
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.1.2';
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaVersion()
    {
        return $this->getVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeveloper()
    {
        return 'Bert Oost';
    }

    /**
     * {@inheritdoc}
     */
    public function getDeveloperUrl()
    {
        return 'https://bertoost.com';
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentationUrl()
    {
        return 'https://github.com/bertoost/Craft-CMS-Mandrill-Service';
    }

    /**
     * {@inheritdoc}
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/bertoost/Craft-CMS-Mandrill-Service/master/releases.json';
    }

    /**
     * {@inheritdoc}
     */
    public function hasCpSection()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if (!$this->loadAutoload()) {
            return false;
        }

        $settings = $this->getSettings();
        $configEnabled = (craft()->config->hasProperty('mandrillEnabled') ? (boolean) craft()->config->get('mandrillEnabled') : null);

        if ($settings->enabled && ((null !== $configEnabled && $configEnabled) || null === $configEnabled)) {

            craft()->on('email.onBeforeSendEmail', function (Event $event) {

                // when not coming from our self
                if (!isset($event->params['_mandrill'])) {

                    $sent = craft()->mandrill
                        ->setEmailModel($event->params['emailModel'])
                        ->setUser($event->params['user'])
                        ->setContentVariables($event->params['variables'])
                        ->send();

                    // store sent state for Craft 
                    $event->params['sent'] = $sent;

                    // stop any 'normal' mail from being send by Craft when succeeded
                    if ($sent) {
                        $event->performAction = false;
                    }
                }
            });
        }
    }

    /**
     * Try to find the vendor autoload from Composer and requires it
     *
     * @return boolean
     */
    private function loadAutoload()
    {
        $vendorPath = false;

        if (defined('COMPOSER_VENDOR_PATH')) {
            $vendorPath = COMPOSER_VENDOR_PATH . '/';
        } else {

            $rootPath = realpath(craft()->path->getAppPath() . '../../') . '/';
            if (file_exists($rootPath . 'vendor/')) {
                $vendorPath = $rootPath . 'vendor/';
            } elseif (file_exists(__DIR__ . '/vendor/')) {
                $vendorPath = __DIR__ . '/vendor/';
            }
        }

        if ($vendorPath !== false && file_exists($vendorPath . 'autoload.php')) {
            require_once $vendorPath . 'autoload.php';

            return true;
        }

        craft()->userSession->setError('Mandrill plugin failure: can\'t find Composer\'s vendor/ path!');

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function registerCpRoutes()
    {
        return [
            'mandrill' => [
                'action' => 'mandrill/index',
            ],
            'mandrill/details/(?P<messageId>\d+)' => [
                'action' => 'mandrill/details',
            ],
            'mandrill/details/(?P<messageId>\d+)/html' => [
                'action' => 'mandrill/detailsHtmlView',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function defineSettings()
    {
        return [
            'enabled'                     => AttributeType::Bool,
            'apiKey'                      => AttributeType::String,
            'immediatelyRegisterOutbound' => AttributeType::Bool,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml()
    {
        $settings = $this->getSettings();

        return craft()->templates->render('mandrill/settings', [
            'settings' => $settings,
        ]);
    }
}
