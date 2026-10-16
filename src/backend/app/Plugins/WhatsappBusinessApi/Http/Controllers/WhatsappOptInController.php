<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person\Person;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptInHistory;
use App\Plugins\WhatsappBusinessApi\Services\WhatsappCustomerSyncService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Plugins / WhatsApp Business API', 'API endpoints for WhatsApp Business API integration')]
class WhatsappOptInController extends Controller {
    public function index(Request $request): JsonResponse {
        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value() ?: 'all';
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $query = Person::query()
            ->select([
                'people.id',
                'people.name',
                'people.surname',
            ])
            ->with([
                'addresses' => fn ($q) => $q->orderByDesc('is_primary'),
            ]);

        $query->leftJoin('whatsapp_business_opt_ins', 'whatsapp_business_opt_ins.person_id', '=', 'people.id')
            ->addSelect([
                'whatsapp_business_opt_ins.opted_in',
                'whatsapp_business_opt_ins.updated_at as opt_in_updated_at',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('people.name', 'LIKE', "%{$search}%")
                    ->orWhere('people.surname', 'LIKE', "%{$search}%")
                    ->orWhereHas('addresses', function ($aq) use ($search) {
                        $aq->where('phone', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($status === 'opted_in') {
            $query->where('whatsapp_business_opt_ins.opted_in', true);
        } elseif ($status === 'opted_out') {
            $query->where('whatsapp_business_opt_ins.opted_in', false);
        } elseif ($status === 'pending') {
            $query->whereNull('whatsapp_business_opt_ins.id');
        }

        $paginated = $query->orderBy('people.id', 'desc')->paginate($perPage);

        $items = collect($paginated->items())->map(function ($person) {
            $primaryAddress = $person->addresses->firstWhere('is_primary', 1) ?? $person->addresses->first();
            $phone = $primaryAddress?->phone;

            return [
                'id' => (int) $person->id,
                'name' => trim($person->name.' '.$person->surname),
                'phone' => $phone ? (string) $phone : 'No phone',
                'opted_in' => $person->opted_in !== null ? (bool) $person->opted_in : null,
                'updated_at' => $person->opt_in_updated_at,
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 200);
    }

    public function getStats(): JsonResponse {
        $personIds = Person::query()->select('id');
        $total = Person::query()->count();
        $optedIn = WhatsappBusinessOptIn::query()
            ->where('opted_in', true)
            ->whereIn('person_id', $personIds)
            ->count();
        $optedOut = WhatsappBusinessOptIn::query()
            ->where('opted_in', false)
            ->whereIn('person_id', $personIds)
            ->count();
        $pending = max(0, $total - ($optedIn + $optedOut));

        return response()->json([
            'data' => [
                'total_customers' => $total,
                'opted_in' => $optedIn,
                'opted_out' => $optedOut,
                'pending' => $pending,
            ],
        ], 200);
    }

    public function bulk(Request $request): JsonResponse {
        $request->validate([
            'opted_in' => ['required', 'boolean'],
            'opt_in_all' => ['sometimes', 'boolean'],
            'person_ids' => ['sometimes', 'array'],
            'person_ids.*' => ['integer'],
        ]);

        $optedIn = $request->boolean('opted_in');

        if ($request->boolean('opt_in_all')) {
            /** @var array<int> $personIds */
            $personIds = Person::query()->pluck('id')->all();
        } else {
            /** @var array<int> $personIds */
            $personIds = (array) $request->input('person_ids', []);
        }

        if (!empty($personIds)) {
            $timestamp = now();
            $actorId = auth('api')->id() ?? auth()->id();
            $chunks = array_chunk($personIds, 500);

            foreach ($chunks as $chunk) {
                $rows = array_map(fn (int $id): array => [
                    'person_id' => $id,
                    'opted_in' => $optedIn,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ], $chunk);

                WhatsappBusinessOptIn::query()->upsert(
                    $rows,
                    ['person_id'],
                    ['opted_in', 'updated_at']
                );

                $historyRows = array_map(fn (int $id): array => [
                    'person_id' => $id,
                    'opted_in' => $optedIn,
                    'actor_type' => 'user',
                    'actor_id' => $actorId,
                    'source' => WhatsappBusinessOptInHistory::SOURCE_BULK_ACTION,
                    'note' => $optedIn ? 'Bulk opt-in via admin panel' : 'Bulk opt-out via admin panel',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ], $chunk);

                WhatsappBusinessOptInHistory::query()->insert($historyRows);
            }
        }

        return response()->json([
            'data' => [
                'count' => count($personIds),
                'opted_in' => $optedIn,
                'message' => 'Successfully updated '.count($personIds).' customer opt-in records',
            ],
        ], 200);
    }

    public function get(int $personId): JsonResponse {
        $optIn = WhatsappBusinessOptIn::query()->where('person_id', $personId)->first();

        return response()->json([
            'data' => [
                'person_id' => $personId,
                'opted_in' => $optIn && $optIn->opted_in,
            ],
        ], 200);
    }

    public function update(Request $request, int $personId): JsonResponse {
        $request->validate([
            'opted_in' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $optedIn = $request->boolean('opted_in');

        $optIn = WhatsappBusinessOptIn::query()->updateOrCreate(
            ['person_id' => $personId],
            ['opted_in' => $optedIn]
        );

        $actorId = auth('api')->id() ?? auth()->id();
        $defaultNote = $optedIn ? 'Opted in via admin panel' : 'Opted out via admin panel';

        WhatsappBusinessOptInHistory::query()->create([
            'person_id' => $personId,
            'opted_in' => $optedIn,
            'actor_type' => 'user',
            'actor_id' => $actorId,
            'source' => WhatsappBusinessOptInHistory::SOURCE_ADMIN_UI,
            'note' => $request->input('note') ?: $defaultNote,
        ]);

        return response()->json([
            'data' => [
                'person_id' => $optIn->person_id,
                'opted_in' => $optIn->opted_in,
            ],
        ], 200);
    }

    public function syncNumber(Request $request, WhatsappCustomerSyncService $syncService): JsonResponse {
        $validated = $request->validate([
            'person_id' => ['required', 'integer'],
            'new_phone' => ['required', 'string'],
        ]);

        /** @var Person $person */
        $person = Person::query()->findOrFail((int) $validated['person_id']);
        $address = $syncService->syncCustomerPhoneNumber($person, (string) $validated['new_phone'], 'api');

        return response()->json([
            'data' => [
                'person_id' => $person->id,
                'phone' => $address->phone,
                'opted_in' => true,
                'message' => 'Customer phone number synchronized successfully in MPM',
            ],
        ], 200);
    }
}
