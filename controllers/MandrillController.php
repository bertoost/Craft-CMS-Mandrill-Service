<?php

namespace Craft;

/**
 * Class MandrillController
 */
class MandrillController extends BaseController
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