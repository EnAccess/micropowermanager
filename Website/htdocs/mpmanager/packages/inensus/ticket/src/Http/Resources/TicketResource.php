<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 28.08.18
 * Time: 13:53.
 */

namespace Inensus\Ticket\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
