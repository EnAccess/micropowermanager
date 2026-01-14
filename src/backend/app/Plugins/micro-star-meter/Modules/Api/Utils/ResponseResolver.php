<?php

namespace Inensus\MicroStarMeter\Modules\Api\Utils;

use Inensus\MicroStarMeter\Exceptions\MicroStarApiResponseException;

class ResponseResolver {
    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    public function checkResponse(array $result): array {
        if (isset($result['errorCode'])) {
            if ($result['errorCode'] == 1000) {
                return $result;
            }

            $responseMessage = $this->getMessage($result['errorCode']);

            throw new MicroStarApiResponseException($responseMessage);
        }

        return $result;
    }

    public function getMessage(int $statusCode): string {
        return match ($statusCode) {
            1000 => 'Success',
            1001 => 'Parameter Error',
            1003 => 'No Result Error',
            1004 => 'Database Error',
            1005 => 'In Process',
            2002 => 'TOKEN rejected',
            2003 => 'TOKEN expired',
            2004 => 'TOKEN used',
            2005 => 'Manufacture code error',
            2006 => 'Key expired error',
            2007 => 'DDTK error',
            2008 => 'Charge amount overflow',
            2009 => 'Key type Error',
            2010 => 'Incorrect TOKEN data format',
            2011 => 'Key range error',
            2012 => 'Function not supported',
            2013 => 'The first section key is accepted and OK',
            2014 => 'The second section key is accepted and OK',
            2015 => 'Lockout After 10 continuous rejections of token inputs, P2000 will lock out for one day (24 hours). During this period, any token entry will be rejected. After one day customers can input tokens again.',
            default => 'Unknown Error',
        };
    }
}
