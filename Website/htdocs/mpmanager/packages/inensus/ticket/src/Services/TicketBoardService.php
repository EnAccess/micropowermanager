<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 23.08.18
 * Time: 13:58
 */

namespace Inensus\Ticket\Services;

use App\Services\BaseService;
use App\Services\IBaseService;
use Inensus\Ticket\Models\TicketBoard;
use Inensus\Ticket\Trello\Boards;
use function config;

class TicketBoardService  implements IBaseService
{

    public function __construct(
        private Boards $boardsGateway,
        private TicketBoard $ticketBoard
    ) {
        parent::__construct([$ticketBoard]);
    }

    public function initializeBoard($ticketUserService): TicketBoard
    {

        return $this->ticketBoard->newQuery()->where('active', 1)->first()
            ?? $this->create($this->createBoard(),$ticketUserService);
    }

    public function create($boardData, $ticketUserService = null)
    {
        $board = $this->ticketBoard->newQuery()->create([
            'board_id' => $boardData['id'],
            'name' => $boardData['name'],
            'active' => true
        ]);

        return $this->addAllMembersToNewlyCreatedBoard($board, $ticketUserService);
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->ticketBoard->newQuery()->paginate($limit);
        }

        return $this->ticketBoard->newQuery()->get();
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function addUsers(string $boardId, $userData)
    {
        $this->boardsGateway->addMemberToBoard($boardId, $userData);
    }

    private function createBoard($name = null)
    {
        $name = $name ?? config('tickets.prefix');

        return $this->boardsGateway->createBoard($name);
    }

    private function addAllMembersToNewlyCreatedBoard($board, $ticketUserService)
    {
        //add all users to the newly added board
        $this->boardsGateway->addMemberToBoard($board->board_id, $ticketUserService->getAll());
        $webHookId = $this->boardsGateway->addCallBack($board->board_id);

        $board->web_hook_id = $webHookId;
        $board->save();

        return $board;
    }
}
