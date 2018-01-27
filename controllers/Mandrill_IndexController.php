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

        $this->renderTemplate('mandrill/outbound/index', $variables);
    }
}