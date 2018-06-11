<?php
namespace Keboola\DarkSkyAugmentation;

class ParametersValidation
{
    public static function validate($config)
    {
        if (!isset($config['storage']['input']['tables']) || ! count($config['storage']['input']['tables'])) {
            throw new Exception("There is no table configured in input mapping");
        }

        if (isset($config['parameters']['conditions']) && !is_array($config['parameters']['conditions'])) {
            throw new Exception("Parameter 'conditions' must be array");
        }

        if (!isset($config['parameters']['#apiToken'])) {
            throw new Exception("Parameter '#apiToken' must be set");
        }
    }
}
