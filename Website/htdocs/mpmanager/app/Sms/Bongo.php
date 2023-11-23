<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 25.05.18
 * Time: 14:43
 */

namespace App\Sms;

use App\Lib\ISmsProvider;
use App\Models\Sms;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\StreamInterface;
use Illuminate\Support\Facades\Log;

class Bongo implements ISmsProvider
{
    private $errorCodes = [
        '-1' => 'Invalid XML format',
        '-2' => 'Not enough credits in account',
        '-3' => 'Invalid API key',
        '-4' => 'Destination Mobile number missing /Invalid format',
        '-5' => 'SMS text missing',
        '-6' => 'Sender name missing / invalid format / Not active in account ',
        '-7' => 'Network not covered',
        '-8' => 'Error Undefined',
        '-9' => 'Invalid message id, too long (max 36) or contains non numeric character.',
        '-10' => 'Maximum number of recipient in one single API call is 100',
        '-11' => 'Error – Undefined',
        '-12' => 'Message too long (max 480 characters)',
        '-13' => 'Invalid Username / Password',
        '-14' => 'Invalid send time',
    ];

    /**
     * @var Sms
     */


    public function __construct()
    {
    }

    public $defer = true;

    /**
     * @param  string $number
     * @param  string $body
     * @return StreamInterface
     * @throws Exception
     */
    public function sendGetSms(string $number, string $body)
    {
        $httpClient = new Client();
        if ($number[0] !== '+') {
            $number = '+' . $number;
        }

        $request = $httpClient->get(
            config()->get('services.sms.bongo.url'),
            [
                'query' => Query::build(
                    [
                    'sendername' => config()->get('services.sms.bongo.sender'),
                    'username' => config()->get('services.sms.bongo.username'),
                    'password' => config()->get('services.sms.bongo.password'),
                    'apikey' => config()->get('services.sms.bongo.key'),
                    'destnum' => $number,
                    'message' => $body,
                    'senddate' => '',
                    ]
                ),
            ]
        );
        $response = (string)$request->getBody();
        if ((int)$response < 0) {
            throw  new Exception($this->errorCodes[$response]);
        }
        return $request->getBody();
    }


    /**
     * @param  string $number
     * @param  string $body
     * @param  string $callback
     * @return mixed|StreamInterface
     * @throws \Exception
     */
    public function sendSms(string $number, string $body, $callback)
    {
        $httpClient = new Client();
        if ($number[0] !== '+') {
            $number = '+' . $number;
        }

        $apikey = "c4a12fa8-ed6f-11df-a1f1-00181236674f";
        $messageXML =
            "<Broadcast>
                <Authentication>
                    <Sendername>" . config()->get('services.sms.bongo.sender') . "</Sendername>
                    <Username>" . config()->get('services.sms.bongo.username') . "</Username>
                    <Password>" . config()->get('services.sms.bongo.password') . "</Password>
                    <Apikey>" . config()->get('services.sms.bongo.key') . "</Apikey>
                </Authentication>
                <Message>
                    <Content>" . $body . " </Content>
                    <Receivers>
                        <Receiver>" . $number . "</Receiver>
                    </Receivers>
                    <Callbackurl>'.$callback.'</Callbackurl>
                </Message>
            </Broadcast>";


        Log::critical('SMS CONTENT', [$messageXML]);

        $headers = [
            'Accept' => 'application/xml',
            'Content-Type' => 'application/x-www-form-urlencoded;',
        ];

        $request = $httpClient->post(
            'http://www.bongolive.co.tz/api/broadcastSMS.php',
            [
                'form_params' => ['messageXML' => $messageXML],
                'headers' => $headers,
            ]
        );
        $response = (string)$request->getBody();

        Log::critical('SMS', [$response, $request->getStatusCode()]);
        if ((int)$response < 0) {
            throw new \Exception($this->errorCodes[$response]);
        }
        return $request->getBody();
    }
}
