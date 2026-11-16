<?php

namespace App\Http\Controllers;

use App\DTO\SmsDataContainer;
use App\Events\SmsStoredEvent;
use App\Exceptions\SmsGatewayNotConfiguredException;
use App\Http\Requests\SmsRequest;
use App\Http\Requests\StoreSmsRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\SmsSearchResultResource;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use App\Plugins\WhatsappMessaging\Services\WhatsappTemplateService;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
use App\Services\SmsGatewayResolverService;
use App\Services\SmsService;
use App\Services\TicketCommentService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller {
    public function __construct(
        private Sms $sms,
        private Person $person,
        private Meter $meter,
        private SmsService $smsService,
        private TicketCommentService $commentService,
        private SmsGatewayResolverService $smsGatewayResolverService,
        private WhatsappTemplateService $whatsappTemplateService,
        private WhatsappMessagingGateway $whatsappGateway,
    ) {}

    private function ensureSmsGatewayIsConfigured(?string $whyWhatsappCannotSend = null): void {
        if (!$this->smsGatewayResolverService->isSmsGatewayConfigured()) {
            throw $whyWhatsappCannotSend === null ? new SmsGatewayNotConfiguredException() : new SmsGatewayNotConfiguredException($whyWhatsappCannotSend);
        }
    }

    public function index(Request $request): ApiResource {
        $term = trim($request->string('term')->toString());

        $list = $this->sms::with('address.owner')
            ->select('receiver', DB::raw('COUNT(*) AS total'))
            ->when($term !== '', fn (Builder $query) => $this->constrainToSearchTerm($query, $term))
            ->groupBy('receiver')
            ->orderByDesc(DB::raw('MAX(created_at)'))
            ->orderBy('receiver')
            ->paginate(20);

        $transformedData = $list->through(fn (object $item): array => SmsDataContainer::fromQuery($item)->toArray());

        return new ApiResource($transformedData);
    }

    /**
     * Narrow the conversation list to receivers whose number, or whose customer's
     * name, contains the term. Conversations are keyed by phone number, so the
     * customer match resolves to the numbers registered against those people.
     *
     * @param Builder<Sms> $query
     */
    private function constrainToSearchTerm(Builder $query, string $term): void {
        $pattern = '%'.$term.'%';

        $query->where(function (Builder $conversationQuery) use ($pattern): void {
            $conversationQuery
                ->where('receiver', 'LIKE', $pattern)
                ->orWhereIn('receiver', Address::query()
                    ->select('phone')
                    ->whereHasMorph(
                        'owner',
                        [Person::class],
                        fn (Builder $ownerQuery) => $ownerQuery
                            ->where('name', 'LIKE', $pattern)
                            ->orWhere('surname', 'LIKE', $pattern)
                    ));
        });
    }

    private function manualTemplate(Request $request, ?string $singleReceiver = null): ?WhatsappTemplate {
        $templateId = $request->string('template_id')->toString();
        if ($templateId === '') {
            if ($singleReceiver === null) {
                $this->ensureSmsGatewayIsConfigured();
            } elseif (!$this->whatsappGateway->canSendFreeText($singleReceiver)) {
                $this->ensureSmsGatewayIsConfigured('No SMS gateway is configured, and WhatsApp only takes free text from customers who are opted in and wrote in the last 24 hours. Send a WhatsApp template instead, or configure an SMS gateway in Main Settings.');
            }

            return null;
        }

        return $this->whatsappTemplateService->manualTemplate($templateId);
    }

    /**
     * The customer's name and surname are filled in per receiver, so one bulk send can greet each customer.
     *
     * @param array<string, string> $templateValues
     *
     * @return array{body: string, values: array<string, string>}
     */
    private function manualMessage(?WhatsappTemplate $template, string $message, array $templateValues, string $phone): array {
        if (!$template instanceof WhatsappTemplate) {
            return ['body' => $message, 'values' => []];
        }

        $person = Address::query()->where('phone', $phone)->where('owner_type', 'person')->first()?->owner;
        $values = [...$templateValues, 'name' => (string) ($person->name ?? ''), 'surname' => (string) ($person->surname ?? '')];

        return ['body' => $template->render($values), 'values' => $values];
    }

    public function storeBulk(Request $request): ApiResource {
        $template = $this->manualTemplate($request);

        $type = $request->string('type')->toString();
        $message = $request->string('message')->toString();
        $templateValues = array_map(strval(...), $request->array('template_values'));
        $senderId = $request->integer('senderId');
        $receivers = $request->array('receivers');

        $phoneNumbers = match ($type) {
            'person' => $receivers,
            'group', 'type', 'all' => $this->resolveBulkPhoneNumbers($type, $receivers, $request->integer('miniGrid')),
            default => [],
        };

        $queued = 0;
        $failedReceivers = [];

        foreach ($phoneNumbers as $phone) {
            ['body' => $body, 'values' => $values] = $this->manualMessage($template, $message, $templateValues, $phone);
            $sms = $this->smsService->createSms([
                'receiver' => $phone,
                'body' => $body,
                'direction' => Sms::DIRECTION_OUTGOING,
                'sender_id' => $senderId,
                'status' => Sms::STATUS_STORED,
            ]);

            try {
                $this->smsService->sendSms(
                    ['message' => $body, 'phone' => $phone, 'template_id' => $template?->meta_template_id, 'template_values' => $values],
                    SmsTypes::MANUAL_SMS,
                    SmsConfigs::class,
                    $sms
                );
                ++$queued;
            } catch (\Throwable $exception) {
                Log::error('Bulk sms send failed.', ['receiver' => $phone, 'message' => $exception->getMessage()]);
                $this->smsService->markFailed($sms, $exception);
                $failedReceivers[] = $phone;
            }
        }

        return new ApiResource([
            'queued' => $queued,
            'failed' => count($failedReceivers),
            'failed_receivers' => $failedReceivers,
        ]);
    }

    /**
     * @param array<int, mixed> $receivers
     *
     * @return array<int, string>
     */
    private function resolveBulkPhoneNumbers(string $type, array $receivers, int $miniGrid): array {
        $query = $this->meter::with([
            'device.person.addresses' => static function ($q) {
                $q->where('is_primary', 1);
            },
        ])->whereHas('device.person.addresses', static function ($q) use ($miniGrid) {
            if ($miniGrid === 0) {
                $q->where('city_id', '>', 0);
            } else {
                $q->where('city_id', $miniGrid);
            }
        });

        $connectionRelation = match ($type) {
            'group' => 'connectionGroup',
            'type' => 'connectionType',
            default => null,
        };

        if ($connectionRelation !== null) {
            $query->whereHas($connectionRelation, static function ($q) use ($receivers) {
                $q->where('id', $receivers);
            });
        }

        return $query->get()
            ->pluck('device.person.addresses')
            ->filter()
            ->map(static fn ($addresses) => $addresses[0]->phone ?? null)
            ->filter()
            ->values()
            ->all();
    }

    public function store(StoreSmsRequest $request): ApiResource {
        $sender = $request->input('sender');
        $message = $request->input('message');
        $smsData = [
            'receiver' => $sender,
            'body' => $message,
            'direction' => Sms::DIRECTION_INCOMING,
            'sender_id' => null,
            'status' => Sms::STATUS_DELIVERED,
        ];
        $sms = $this->smsService->createSms($smsData);

        match ($this->smsService->checkMessageType($message)) {
            $this->smsService::FEEDBACK => event(new SmsStoredEvent($sender, $message, $sms)),
            $this->smsService::TICKET => $this->commentService->storeComment($sender, $message),
            default => new ApiResource($sms),
        };

        return new ApiResource($sms);
    }

    public function storeAndSend(SmsRequest $request): ApiResource {
        $requestPhone = $request->string('phone')->toString() ?: null;
        if ($request->filled('person_id')) {
            // get person primary phone; fall back to request phone if missing
            $phone = Address::where('owner_type', 'person')
                ->where('owner_id', $request->integer('person_id'))
                ->where('is_primary', 1)
                ->value('phone')
                ?? $requestPhone;
        } else {
            $phone = $requestPhone;
        }

        if (!$phone) {
            // raise exception
            throw new \Exception('Phone number is required for sending SMS.');
        }

        $template = $this->manualTemplate($request, $phone);
        ['body' => $body, 'values' => $values] = $this->manualMessage(
            $template,
            $request->string('message')->toString(),
            array_map(strval(...), $request->array('template_values')),
            $phone,
        );
        $smsData = [
            'receiver' => $phone,
            'body' => $body,
            'direction' => Sms::DIRECTION_OUTGOING,
            'sender_id' => $request->filled('senderId') ? $request->integer('senderId') : null,
            'status' => Sms::STATUS_STORED,
        ];
        $sms = $this->smsService->createAndSendSms($smsData, $template?->meta_template_id, $values);

        return new ApiResource($sms);
    }

    public function show(int $person_id): ApiResource {
        $personAddresses = $this->person::with(
            [
                'addresses' => function ($q) {
                    $q->select(DB::raw('phone'), 'owner_id');
                },
            ]
        )
            ->where('id', $person_id)
            ->first();
        $numbers = $personAddresses->addresses->toArray();
        $smses = $this->sms::with('deliveryAttempts')->whereIn('receiver', $numbers)->orderBy('id', 'ASC')->get();

        return new ApiResource($smses);
    }

    public function byPhone(string $phone): ApiResource {
        $smses = $this->sms->with('deliveryAttempts')->where('receiver', $phone)->get();

        return new ApiResource($smses);
    }

    public function search(string $search): AnonymousResourceCollection {
        // search in people
        $list = $this->person::with('addresses')
            ->whereHas(
                'addresses',
                function ($q) use ($search) {
                    $q->where('phone', 'like', '%'.$search.'%')
                        ->where('is_primary', 1);
                }
            )
            ->orWhere('name', 'like', '%'.$search.'%')
            ->orWhere('surname', 'like', '%'.$search.'%')
            ->get();

        return SmsSearchResultResource::collection($list);
    }
}
