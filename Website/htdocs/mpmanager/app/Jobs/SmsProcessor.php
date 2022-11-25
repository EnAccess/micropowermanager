<?php

namespace App\Jobs;

use App\Exceptions\SmsAndroidSettingNotExistingException;
use App\Exceptions\SmsBodyParserNotExtendedException;
use App\Exceptions\SmsTypeNotFoundException;
use App\Models\Sms;
use App\Sms\Senders\ManualSms;
use App\Sms\Senders\SmsSender;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SmsProcessor extends AbstractJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $data;
    public $smsType;
    private $smsConfigs;
    private $smsAndroidSettings;


    /**
     * Create a new job instance.
     *
     * @param     $data
     * @param int $smsType
     * @param $smsConfigs
     */
    public function __construct($data, int $smsType, $smsConfigs)
    {
        $this->data = $data;
        $this->smsType = $smsType;
        $this->smsConfigs = $smsConfigs;
        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob()
    {
        try {
            $this->getSmsAndroidSettings();
            $smsType = $this->resolveSmsType();
        } catch (SmsTypeNotFoundException $exception) {
            Log::critical('Sms send failed.', ['message : ' => $exception->getMessage()]);
            return;
        } catch (SmsBodyParserNotExtendedException $exception) {
            Log::critical('Sms send failed.', ['message : ' => $exception->getMessage()]);
            return;
        } catch (SmsAndroidSettingNotExistingException $exception) {
            Log::critical('Sms send failed.', ['message : ' => $exception->getMessage()]);
            return;
        }

        $receiver = $smsType->getReceiver();
        //dont send sms if debug
        if (config('app.debug')) {
            if (!($smsType instanceof ManualSms)) {
                $sms = Sms::query()->make([
                    'body' => $smsType->body,
                    'receiver' => $receiver,
                    'uuid' => "debug"
                ]);
                $sms->trigger()->associate($this->data);
                $sms->save();
                return;
            }
        }
        try {
            //set the uuid for the callback
            $uuid = $smsType->generateCallback($this->smsAndroidSettings->callback);
            //sends sms or throws exception
            $smsType->sendSms();
        } catch (Exception $e) {
            //slack failure
            Log::critical(
                'Sms Service failed ' . $receiver,
                ['id' => '58365682988725', 'reason' => $e->getMessage()]
            );
            return;
        }
        if (!($smsType instanceof ManualSms)) {
            $sms = Sms::query()->make([
                'uuid' => $uuid,
                'body' => $smsType->body,
                'receiver' => $receiver
            ]);
            $sms->trigger()->associate($this->data);
            $sms->save();
        } else {
            $lastSentManualSms = Sms::query()->where('receiver', $receiver)->where(
                'body',
                $smsType->body
            )->latest()->first();
            if ($lastSentManualSms) {
                $lastSentManualSms->update([
                    'uuid' => $uuid,
                ]);
            }
        }
    }

    private function getSmsAndroidSettings()
    {
        $smsAndroidSettingsService = resolve('AndroidSettingsService');
        $this->smsAndroidSettings = $smsAndroidSettingsService->getResponsible();
    }

    private function resolveSmsType()
    {
        $configs = resolve($this->smsConfigs);
        if (!array_key_exists($this->smsType, $configs->smsTypes)) {
            throw new  SmsTypeNotFoundException('SmsType could not resolve.');
        }
        $smsBodyService = resolve($configs->servicePath);
        $reflection = new \ReflectionClass($configs->smsTypes[$this->smsType]);
        if (!$reflection->isSubclassOf(SmsSender::class)) {
            throw new  SmsBodyParserNotExtendedException('SmsBodyParser has not extended.');
        }
        return $reflection->newInstanceArgs([
            $this->data,
            $smsBodyService,
            $configs->bodyParsersPath,
            $this->smsAndroidSettings
        ]);
    }
}
