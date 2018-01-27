<?php

namespace Craft;

/**
 * Class Mandrill_IndexController
 */
class Mandrill_IndexController extends BaseController
{
    /**
     * @throws HttpException
     */
    public function actionIndex()
    {
        $variables = [
            'elementType' => MandrillModel::ElementTypeOutbound,
        ];

        craft()->templates->includeCssResource('mandrill/css/style.css');

        $this->renderTemplate('mandrill/outbound/index', $variables);
    }
}