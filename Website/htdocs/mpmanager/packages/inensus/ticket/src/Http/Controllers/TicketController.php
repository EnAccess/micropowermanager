<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 21.06.18
 * Time: 13:48
 */

namespace Inensus\Ticket\Http\Controllers;


use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Models\Label;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Services\BoardService;
use Inensus\Ticket\Services\CardService;
use Inensus\Ticket\Services\TicketService;
use Inensus\Ticket\Services\UserService;


class TicketController extends Controller
{
    private $boardService;
    private $cardService;
    private $ticketService;
    /**
     * @var Label
     */
    private $label;
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(
        BoardService $boardService,
        CardService $cardService,
        TicketService $ticketService,
        UserService $userService,
        Label $label
    ) {
        $this->boardService = $boardService;
        $this->cardService = $cardService;
        $this->ticketService = $ticketService;

        $this->label = $label;
        $this->userService = $userService;
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
    public function index(): TicketResource
    {
        $person = request('person');
        $category = request('category');
        if (request('status') !== null) {
            $tickets = Ticket::with('category', 'owner', 'assignedTo')
                ->where('status', request('status'));
            if ($person) {
                $tickets = $tickets->where('assigned_id', $person);
            }
            if ($category) {
                $tickets = $tickets->where('category_id', $category);
            }

            $tickets = $tickets
                ->latest()
                ->paginate(5);

        } else {
            $tickets = Ticket::with('category', 'owner', 'assignedTo')->latest()->paginate(5);
        }

        //$fetchetdTickets = $this->ticketService->getBatch($tickets);
        return new TicketResource($tickets);
    }

    public function show()
    {
        $trelloId = request('trelloId');
        $ticket = Ticket::with('category', 'owner')->where('ticket_id', $trelloId)->first();
        $ticket['ticket'] = $this->ticketService->getTicket($trelloId);
        $ticket['actions'] = $this->ticketService->getActions($trelloId);
        return new TicketResource(collect($ticket));

    }

    public function create(Request $request): TicketResource
    {

        $ownerId = (int)$request->get('owner_id');
        $ownerType = $request->get('owner_type');
        $assignedId = $request->get('assignedPerson');


        $board = $this->boardService->initializeBoard();
        $card = $this->cardService->initalizeList($board);
        $creatorId = $request->get('creator');
        $creatorType = $request->get('creator_type');

        //reformat due date if it is set
        $dueDate = $request->get('dueDate') !== null ? date('Y-m-d H:i:00', strtotime($request->get('dueDate'))) : null;
        $category = $request->get('label');


        $trelloParams = [
            'idList' => $card->card_id,
            'name' => $request->get('title'),
            'desc' => $request->get('description'),
            'due' => $dueDate === '1970-01-01' ? null : $dueDate,
            'idMembers' => $assignedId,

        ];

        $assignedUser = $assignedId;
        $ticket = $this->ticketService->create(
            $creatorId,
            $creatorType,
            $ownerId,
            $ownerType,
            $category,
            $assignedUser,
            $trelloParams
        );
        //get category to check outsourcing
        $categoryData = $this->label->find($category);
        if ($categoryData->out_source) {
            $ticket->outsource()->create(['amount' => (int)$request->get('outsourcing')]);
        }


        return new TicketResource($this->ticketService->getBatch([$ticket]));

    }

    public function destroy(Request $request)
    {
        $ticket = $request->get('ticketId');
        $ticketId=$ticket['id'];
        if ($ticketId === null) {
            return (new TicketResource([]))->response()->setStatusCode(404);
        }
        $closed = $this->ticketService->close($ticketId);

        return new TicketResource(['data' => $closed]);
    }

    public function getTickets(int $owner_id)
    {
        $tickets = Ticket::with('owner', 'category')->where('owner_id', $owner_id)->latest()->get();

        $fetchedTickets = $this->ticketService->getBatch($tickets);
        return new TicketResource($this->paginateCollection(collect($fetchedTickets), 15));

    }

    public function paginateCollection($collection, $perPage, $pageName = 'page', $fragment = null)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage($pageName);
        $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage);
        parse_str(request()->getQueryString(), $query);
        unset($query[$pageName]);
        $paginator = new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'pageName' => $pageName,
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $query,
                'fragment' => $fragment,

            ]
        );

        return $paginator;
    }

    public function indexAgentTickets($agent_id): TicketResource
    {

        $tickets = Ticket::with('category', 'owner', 'assignedTo')
            ->where('creator_type', 'agent')
            ->where('creator_id', $agent_id)
            ->latest()
            ->paginate(5);

        return new TicketResource($tickets);
    }

}
