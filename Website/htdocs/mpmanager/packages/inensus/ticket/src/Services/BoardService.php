<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 23.08.18
 * Time: 13:58
 */

namespace Inensus\Ticket\Services;


use Inensus\Ticket\Models\Board;
use Inensus\Ticket\Trello\Boards;
use function config;

class BoardService
{
    private $boardModel;
    private $boards;
    private $userService;

    public function __construct(Boards $boards, Board $board, UserService $userService)
    {
        $this->boardModel = $board;
        $this->boards = $boards;
        $this->userService = $userService;
    }


    public function initializeBoard(): Board
    {
        $board = Board::where('active', 1)->first() ?? $this->saveBoard($this->createBoard());
        return $board;
    }

    /**
     * Creates a new board with a random name
     */
    private function createBoard($name = null)
    {
        $name = $name ?? config('tickets.prefix');
        $name .= time();

        return $this->boards->createBoard($name);
    }

    private function saveBoard($boardData)
    {
        $board = new Board();
        $board->board_id = $boardData->id;
        $board->board_name = $boardData->name;
        $board->active = true;

        //add all users to the newly added board
        $this->boards->addMemberToBoard($board->board_id, $this->userService->getAllUsers());
        $webHookId = $this->boards->addCallBack($board->board_id);
        $board->web_hook_id = $webHookId->id;
        $board->save();
        return $board;
    }

    public function getBoards()
    {
        return $this->boardModel->get();
    }

    public function addUsers(string $boardId, $userData)
    {
        $this->boards->addMemberToBoard($boardId, $userData);
    }

}
