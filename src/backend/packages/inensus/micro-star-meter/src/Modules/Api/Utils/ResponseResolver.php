<?php

namespace Inensus\MicroStarMeter\Modules\Api\Utils;

use Inensus\MicroStarMeter\Exceptions\MicroStarApiResponseException;

class ResponseResolver {
    /**
     * @throws MicroStarApiResponseException
     */
    public function checkResponse($result) {
        if (isset($result['errorCode'])) {
            if ($result['errorCode'] == 1000) {
                return $result;
            }

            $responseMessage = $this->getMessage($result['errorCode']);

            throw new MicroStarApiResponseException($responseMessage);
        }

        return $result;
    }

    public function getMessage($statusCode): string {
        switch ($statusCode) {
            case 1000:
                return 'Success';
            case 1001:
                return 'Parameter Error';
            case 1003:
                return 'No Result Error';
            case 1004:
                return 'Database Error';
            case 1005:
                return 'In Process';
            case 2002:
                return 'TOKEN rejected';
            case 2003:
                return 'TOKEN expired';
            case 2004:
                return 'TOKEN used';
            case 2005:
                return 'Manufacture code error';
            case 2006:
                return 'Key expired error';
            case 2007:
                return 'DDTK error';
            case 2008:
                return 'Charge amount overflow';
            case 2009:
                return 'Key type Error';
            case 2010:
                return 'Incorrect TOKEN data format';
            case 2011:
                return 'Key range error';
            case 2012:
                return 'Function not supported';
            case 2013:
                return 'The first section key is accepted and OK';
            case 2014:
                return 'The second section key is accepted and OK';
            case 2015:
                return 'Lockout After 10 continuous rejections of token inputs, P2000 will lock out for one day (24 hours). During this period, any token entry will be rejected. After one day customers can input tokens again.';

            default:
                return 'Unknown Error';
        }
    }
}
