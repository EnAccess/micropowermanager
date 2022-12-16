<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 21.06.18
 * Time: 13:48
 */

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Services\TicketService;


class TicketController extends Controller
{
    const FOR_APP = false;

    public function __construct(private TicketService $ticketService)
    {

    }

    public function index(Request $request): TicketResource
    {
        $assignedId = $request->input('person');
        $categoryId = $request->input('category');
        $status = $request->input('status');
        $limit = 5;
        return TicketResource::make($this->ticketService->getAll(limit:$limit, status:$status, assignedId: $assignedId,  categoryId: $categoryId));
    }

    public function show(int $id ): TicketResource
    {
        $ticket = $this->ticketService->getById($id);

        return TicketResource::make(collect($ticket));
    }

    // TODO: change this on UI side with query parameter $ticketId
    public function destroy(int $ticketId,Request $request)
    {
        $closed = $this->ticketService->close($ticketId);

        return TicketResource::make(['data' => $closed]);
    }


}
