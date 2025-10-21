<?php

namespace Inensus\KelinMeter\Http\Resources;

use App\Models\Person\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inensus\KelinMeter\Models\KelinCustomer as KelinCustomerData;

/**
 * @mixin KelinCustomerData
 */
class KelinCustomerResource extends JsonResource {
    /**
     * @return array{
     *     data: array{
     *         type: 'customer',
     *         id: int,
     *         attributes: array{
     *             id: int,
     *             customerNo: string,
     *             mpmPerson: Person,
     *             phone: string,
     *             address: string
     *         }
     *     }
     * }
     */
    public function toArray(Request $request) {
        return [
            'data' => [
                'type' => 'customer',
                'id' => $this->id,
                'attributes' => [
                    'id' => $this->id,
                    'customerNo' => $this->customer_no,
                    'mpmPerson' => $this->mpmPerson,
                    'phone' => $this->mobile,
                    'address' => $this->address,
                ],
            ],
        ];
    }
}
