<?php

namespace App\Services;

use App\Models\AccessRate\AccessRatePayment;

class AccessRatePaymentService implements IBaseService
{
    
    public function __construct(private AccessRatePayment $accessRatePayment)
    {
    }

    public function getById($id)
    {
        return $this->accessRatePayment->newQuery()->find($id);
    }

    public function create($accessRatePaymentData)
    {
        return $this->accessRatePayment->newQuery()->create($accessRatePaymentData);
    }

    public function update($accessRatePayment, $accessRatePaymentData)
    {
        $accessRatePayment->update($accessRatePaymentData);
        $accessRatePayment->fresh();

        return $accessRatePayment;
    }

    public function delete($accessRatePayment)
    {
        return $accessRatePayment->delete();
    }

    public function getAll($limit = null)
    {
        $query = $this->accessRatePayment->newQuery();
        
        if($limit) {
            return $query->paginate($limit);
        }
        
        return $this->accessRatePayment->newQuery()->get();
    }

    public function getAccessRatePaymentByMeter($meter)
    {
        return $meter->accessRatePayment()->first();
    }

}