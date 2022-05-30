<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 17.08.18
 * Time: 11:24
 */

namespace Inensus\Ticket\Trello;

use App\Exceptions\TrelloAPIException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Inensus\Ticket\Models\TicketBoard;
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
        if (!is_string($boardName)) {
            throw new Exception('Board name must be a string');
        }

        $board = $this->getExistingBoardIfCreated();

        if ($board) {
            return $board;
        }

        $request = $this->api->request('boards', null, $this->api::POST, ['name' => $boardName]);

        if ($request->getStatusCode() !== 200) {
            throw new Exception('4357345df8490flw94');
        }

        return json_decode($request->getBody(), true);
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

        try {
            $request = $this->api->request('webhooks', '', $this->api::POST, $postData);
            $webhook = json_decode($request->getBody(), true);

            return $webhook['id'];
        } catch (GuzzleException $e) {

            if ( str_contains($e->getMessage(),'A webhook with that callback, model, and token already exists')) {

                return config('tickets.webhookId');
            }

            throw new TrelloAPIException($e->getMessage());
        }


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
     */
    public function getExistingBoardIfCreated()
    {
        //check if the 'MicroPowerManager' board exists
        $request = $this->api->request('board', config('tickets.boardId'), api::GET);
        if ($request->getStatusCode() === 200) {
            return json_decode($request->getBody(), true);
        } else {
            return null;
        }

    }

}
