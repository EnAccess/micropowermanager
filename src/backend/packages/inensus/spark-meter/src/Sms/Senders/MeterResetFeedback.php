<?php

namespace Inensus\SparkMeter\Sms\Senders;

use App\Exceptions\MissingSmsReferencesException;
use App\Sms\Senders\SmsSender;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Sms\BodyParsers\SparkSmsMeterResetFailedFeedbackBody;
use Inensus\SparkMeter\Sms\BodyParsers\SparkSmsMeterResetFeedbackBody;

class MeterResetFeedback extends SmsSender {
    protected $data;
    public $body = '';
    protected $references = [
        'header' => 'SparkSmsMeterResetFeedbackHeader',
        'body' => 'SparkSmsMeterResetFeedbackBody',
        'footer' => 'SparkSmsMeterResetFeedbackFooter',
    ];

    public function prepareBody() {
        if (!is_array($this->data)) {
            try {
                $smsBody = $this->smsBodyService->getSmsBodyByReference('SparkSmsMeterResetFeedbackBody');
            } catch (ModelNotFoundException $exception) {
                $exception = new MissingSmsReferencesException('SparkSmsMeterResetFeedback SMS body 
                record not found in database');
                Log::error('SMS Body preparing failed.', ['message : ' => $exception->getMessage()]);

                return;
            }
            $smsObject = new SparkSmsMeterResetFeedbackBody($this->data);
            try {
                $this->body .= $smsObject->parseSms($smsBody->body);
            } catch (\Exception $exception) {
                Log::error('SMS Body parsing failed.', ['message : ' => $exception->getMessage()]);

                return;
            }
        } else {
            try {
                $smsBody = $this->smsBodyService->getSmsBodyByReference('SparkSmsMeterResetFailedFeedbackBody');
            } catch (ModelNotFoundException $exception) {
                $exception = new MissingSmsReferencesException('SparkSmsMeterResetFailedFeedback SMS body 
                record not found in database');
                Log::error('SMS Body preparing failed.', ['message : ' => $exception->getMessage()]);

                return;
            }
            $smsObject = new SparkSmsMeterResetFailedFeedbackBody($this->data);
            try {
                $this->body .= $smsObject->parseSms($smsBody->body);
            } catch (\Exception $exception) {
                Log::error('SMS Body parsing failed.', ['message : ' => $exception->getMessage()]);

                return;
            }
        }
    }
}
