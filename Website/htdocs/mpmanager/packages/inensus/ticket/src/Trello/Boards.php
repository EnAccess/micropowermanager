<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 17.08.18
 * Time: 11:24
 */

namespace Inensus\Ticket\Trello;

use Exception;
use Inensus\Ticket\Models\Board;
use function is_string;
use function json_decode;

class Boards
{

    private $api;


    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * Looks into the collection for the given name.
     *
     * @param $boardName
     * @param $boardCollection
     * @return null|mixed
     */
    private function checkBoardName($boardName, $boardCollection)
    {
        $matchingBoard = null;
        foreach ($boardCollection as $board) {
            if ($board->name === $boardName) {
                $matchingBoard = $board;
                break;
            }
        }
        return $matchingBoard;
    }

    /** Creates a new board on trello
     *
     * @param string $boardName
     * @return mixed
     * @throws Exception
     */
    public function createBoard(string $boardName)
    {
        $request = $this->api->request('boards', null, $this->api::POST, ['name' => $boardName,]);
        if ($request->getStatusCode() !== 200) {
            throw new Exception('4357345df8490flw94');
        }
        return json_decode($request->getBody());
    }

    /**
     * Adds a web hook for all actions happening on the board
     *
     * @param $boardId
     * @return mixed
     * @throws Exception
     */
    public function addCallBack($boardId)
    {
        $callbackURL = config('tickets.callback');
        $idModel = $boardId;
        $active = true;
        $postData = [
            'idModel' => $idModel,
            'callbackURL' => $callbackURL,
            'active' => $active,
        ];

        $request = $this->api->request('webhooks', '', $this->api::POST, $postData);
        if ($request->getStatusCode() !== 200) {
            throw new Exception('5643875643c87');
        }
        return json_decode($request->getBody());

    }

    /**
     * Adds a member to the board
     *
     * @param string $boardId
     * @param $userData
     */
    public function addMemberToBoard(string $boardId, $userData): void
    {
        if (is_string($userData)) { // a single user
            $this->api->request('boards/' . $boardId, 'members/' . $userData, $this->api::PUT, ['type' => 'normal']);
        } else { // collection of users
            foreach ($userData as $u) {
                $this->api->request('boards/' . $boardId, 'members/' . $u->extern_id, $this->api::PUT,
                    ['type' => $u->extern_id === '51640fc2dd104cfa6f0014aa' ? 'admin' : 'normal']);
            }
        }
    }

    /**
     * Initializes the board ;
     * It checks whether an existing active board exists or creates a new one
     *
     * @throws Exception
     * @return  Board
     */
    public function initialize(): Board
    {
        //check if the 'Jumeme' board exists
        $request = $this->api->request('members', 'alikemalzkan/boards', api::GET,
            ['lists' => 'open', 'fields' => 'id,name']);
        //create a new 'Jumeme' board
        if ($request->getStatusCode() === 200) {
            $board = $this->checkBoardName('Your-Company', json_decode($request->getBody())) ?? $this->createBoard('Your-Company');
        } else {
            throw new Exception($request->getBody(), $request->getStatusCode());
        }
        return $board;
    }

}
