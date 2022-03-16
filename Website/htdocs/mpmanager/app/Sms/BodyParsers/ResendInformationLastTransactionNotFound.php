<?php

namespace App\Sms\BodyParsers;

class ResendInformationLastTransactionNotFound extends SmsBodyParser
{
    protected $variables = ['meter'];

    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }
    protected function getVariableValue($variable)
    {

        return $this->data['meter'];
    }
}
