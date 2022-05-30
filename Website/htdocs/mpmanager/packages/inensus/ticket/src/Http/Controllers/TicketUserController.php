<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 28.08.18
 * Time: 13:20
 */

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\Ticket\Http\Requests\TicketingUserRequest;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Models\TicketUser;
use Inensus\Ticket\Services\TicketBoardService;
use Inensus\Ticket\Services\TicketCardService;
use Inensus\Ticket\Services\TicketUserService;

class TicketUserController extends Controller
{
    private $board;
    private $card;

    public function __construct(
        private TicketBoardService $ticketBoardService,
        private TicketCardService $ticketCardService,
        private TicketUserService $ticketUserService,

    ) {

    }

    public function index(Request $request): TicketResource
    {
        $limit = $request->input('limit');
        $outSource = $request->input('outsource');

        return TicketResource::make($this->ticketUserService->getAll($limit, $outSource));
    }

    /**
     * Stores a new Trello User to the Database.
     * !! important !!
     * The user should  exists on Trello.com
     *
     * @param TicketingUserRequest $request
     *
     * @return TicketResource
     */
    public function store(TicketingUserRequest $request): TicketResource
    {
        $this->board = $this->ticketBoardService->initializeBoard($this->ticketUserService);
        $this->card = $this->ticketCardService->initalizeList($this->board);

        $userTag = $request->input('usertag');
        //try to find the user id
        $externalUser = $this->ticketUserService->getByTag($userTag);

        if (!$externalUser) {
            return TicketResource::make([
                'data' => [
                    'error' => "User not found",
                ],
            ]);
        }
        $ticketUserData = [
            'user_name' => $request->input('username'),
            'user_tag' => $request->input('usertag'),
            'out_source' => (bool)$request->input('outsource') ? 1 : 0,
            'extern_id' => $externalUser->id,
        ];
        $ticketUser = $this->ticketUserService->create($ticketUserData);
        //add user to all boards
        $boards = $this->ticketBoardService->getAll();

        //iterate into the boards object
        foreach ($boards as $board) {
            $this->ticketBoardService->addUsers($board->board_id, (string)$ticketUser->extern_id);
        }

        return TicketResource::make($ticketUser);
    }


}
