<?php

use App\Models\MpmPlugin;
use App\Models\Sms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// SMS History becomes the only message log: every try on every channel is an sms_attempts row, so the WhatsApp log moves in here.
return new class extends Migration {
    private const array COPIED_COLUMNS = [
        'sms_id', 'message_type', 'template_name', 'status', 'error_code', 'error_message',
        'sent_at', 'delivered_at', 'read_at', 'failed_at', 'created_at', 'updated_at',
    ];

    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('sms_attempts')) {
            Schema::connection('tenant')->create('sms_attempts', static function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sms_id')->index();
                $table->unsignedInteger('gateway_id');
                $table->string('external_id')->nullable()->unique();
                $table->string('message_type')->nullable();
                $table->string('template_name')->nullable();
                $table->json('request')->nullable();
                $table->string('status');
                $table->integer('error_code')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::connection('tenant')->hasTable('whatsapp_messages')) {
            DB::connection('tenant')->transaction(function (): void {
                $columns = implode(', ', self::COPIED_COLUMNS);
                DB::connection('tenant')->insert(sprintf(
                    'INSERT INTO sms_attempts (gateway_id, external_id, %1$s) SELECT %2$d, wamid, %1$s FROM whatsapp_messages ORDER BY id',
                    $columns,
                    MpmPlugin::WHATSAPP_MESSAGING,
                ));

                $copied = DB::connection('tenant')->table('sms_attempts')->where('gateway_id', MpmPlugin::WHATSAPP_MESSAGING)->count();
                $logged = DB::connection('tenant')->table('whatsapp_messages')->count();
                if ($copied !== $logged) {
                    throw new RuntimeException(sprintf('Only %d of %d WhatsApp messages were copied to sms_attempts; whatsapp_messages is kept.', $copied, $logged));
                }
            });

            Schema::connection('tenant')->drop('whatsapp_messages');
        }
    }

    public function down(): void {
        DB::connection('tenant')->table('sms')
            ->whereIn('status', [Sms::STATUS_WAITING_FOR_RETRY, Sms::STATUS_SENDING_BY_SMS])
            ->update(['status' => Sms::STATUS_FAILED]);

        if (!Schema::connection('tenant')->hasTable('whatsapp_messages')) {
            Schema::connection('tenant')->create('whatsapp_messages', static function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sms_id')->index();
                $table->unsignedInteger('whatsapp_contact_id')->nullable()->index();
                $table->string('wamid')->nullable()->unique();
                $table->string('message_type');
                $table->string('template_name')->nullable();
                $table->string('status');
                $table->integer('error_code')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();
            });
        }

        $columns = implode(', ', self::COPIED_COLUMNS);
        DB::connection('tenant')->insert(sprintf(
            'INSERT INTO whatsapp_messages (wamid, %1$s) SELECT external_id, %1$s FROM sms_attempts WHERE gateway_id = %2$d ORDER BY id',
            $columns,
            MpmPlugin::WHATSAPP_MESSAGING,
        ));

        Schema::connection('tenant')->dropIfExists('sms_attempts');
    }
};
