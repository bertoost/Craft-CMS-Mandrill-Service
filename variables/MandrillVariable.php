<?php

namespace Craft;

/**
 * Class MandrillVariable
 */
class MandrillVariable
{
    /**
     * @return string
     */
    public function getName()
    {
        return craft()->plugins->getPlugin('mandrill')->getName();
    }
}