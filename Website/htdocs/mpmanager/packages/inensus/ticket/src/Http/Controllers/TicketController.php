<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 21.06.18
 * Time: 13:48
 */

namespace Inensus\Ticket\Http\Controllers;


use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Services\PersonService;
use App\Services\PersonTicketService;
use App\Services\UserTicketService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inensus\Ticket\Exceptions\TicketOwnerNotFoundException;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Models\TicketCategory;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Services\TicketBoardService;
use Inensus\Ticket\Services\TicketCardService;
use Inensus\Ticket\Services\TicketCategoryService;
use Inensus\Ticket\Services\TicketOutSourceService;
use Inensus\Ticket\Services\TicketService;
use Inensus\Ticket\Services\TicketUserService;


class TicketController extends Controller
{
    const FOR_APP = false;

    public function __construct(private TicketService $ticketService)
    {

    }

    /**
     * List of created Tickets.
     *
     * @return TicketResource
     *
     * @response {
     * "current_page": 1,
     * "data": [
     * {
     * "id": 2238,
     * "ticket_id": "5e7cee5c689b1a2ee9579a11",
     * "creator_id": 1,
     * "owner_type": "person",
     * "owner_id": 5308,
     * "status": 0,
     * "category_id": 19,
     * "created_at": "2020-03-26 18:03:08",
     * "updated_at": "2020-03-26 18:03:08",
     * "assigned_id": null,
     * "category": {
     * "id": 19,
     * "label_name": "Meter Malfunctioning",
     * "label_color": "yellow",
     * "created_at": "2019-07-17 07:07:33",
     * "updated_at": "2019-07-17 07:07:33",
     * "out_source": 1
     * },
     * "owner": {
     * "id": 5308,
     * "title": null,
     * "education": null,
     * "name": "Maingu",
     * "surname": "Mapesa- Bwisya",
     * "birth_date": null,
     * "sex": "male",
     * "nationality": null,
     * "created_at": "2020-01-17 06:47:41",
     * "updated_at": "2020-02-05 10:18:05",
     * "customer_group_id": null,
     * "is_customer": 1,
     * "deleted_at": null
     * },
     * "assigned_to": null
     * }
     * ],
     * "first_page_url": "http:\/\/localhost\/tickets?page=1",
     * "from": 1,
     * "last_page": 442,
     * "last_page_url": "http:\/\/localhost\/tickets?page=442",
     * "next_page_url": "http:\/\/localhost\/tickets?page=2",
     * "path": "http:\/\/localhost\/tickets",
     * "per_page": 5,
     * "prev_page_url": null,
     * "to": 5,
     * "total": 999
     * }
     */
    public function index(Request $request): TicketResource
    {
        $assignedId = $request->input('person');
        $categoryId = $request->input('category');
        $status = $request->input('status');
        $limit = 5;

        return TicketResource::make($this->ticketService->getAll($limit, $status, null, null, $assignedId, $categoryId));
    }

    public function show($trelloId, Request $request): TicketResource
    {
        $ticket = $this->ticketService->getByTrelloId($trelloId);
        $ticket['ticket'] = $this->ticketService->getTicket($trelloId);
        $ticket['actions'] = $this->ticketService->getActions($trelloId);

        return TicketResource::make(collect($ticket));
    }

    // TODO: change this on UI side with query parameter $ticketId
    public function destroy($ticketId,Request $request)
    {
        $closed = $this->ticketService->close($ticketId);

        return TicketResource::make(['data' => $closed]);
    }


}
