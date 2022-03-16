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
use Inensus\Ticket\Models\UserModel;
use Inensus\Ticket\Services\BoardService;
use Inensus\Ticket\Services\UserService;

class UserController extends Controller
{
    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * @var UserService
     */
    private $userService;
    private $boardService;

    public function __construct(UserModel $model, UserService $userService, BoardService $boardService)
    {
        $this->userModel = $model;
        $this->userService = $userService;
        $this->boardService = $boardService;
    }

    /**
     * A list of registered Trello users.
     *
     * @param Request $request
     *
     * @return TicketResource
     */
    public function index(Request $request): TicketResource
    {
        $outSource = $request->get('outsource');
        if ($outSource) {
            $users = $this->userModel::where('out_source', 1)->get();
        } else {
            $users = $this->userModel::all();
        }
        return new TicketResource($users);
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
        $userTag = request('usertag');

        //try to find the user id
        $externalUser = $this->userService->getUser($userTag);

        if ($externalUser === null) {
            return new TicketResource([
                'data' => [
                    'error' => "User not found",
                ],
            ]);
        }

        $this->userModel->user_name = $request->get('username');
        $this->userModel->user_tag = $request->get('usertag');
        $this->userModel->out_source = (bool)$request->get('outsource') ? 1 : 0;
        $this->userModel->extern_id = $externalUser->id;

        $this->userModel->save();

        //add user to all boards
        $boards = $this->boardService->getBoards();
        //iterate into the boards object
        foreach ($boards as $b) {
            $this->boardService->addUsers($b->board_id, (string)$this->userModel->extern_id);
        }
        return new TicketResource($this->userModel);
    }


}
