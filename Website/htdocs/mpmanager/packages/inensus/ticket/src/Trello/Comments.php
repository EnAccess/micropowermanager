<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 26.09.18
 * Time: 15:22
 */

namespace Inensus\Ticket\Trello;

use App\Exceptions\TrelloAPIException;
use GuzzleHttp\Exception\GuzzleException;
use Inensus\Ticket\Interfaces\ITicketProvider;

class Comments
{

    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }


    public function newComment($cardId, $comment)
    {
        try {
            return $this->api->request('cards', $cardId . '/actions/comments', $this->api::POST, ['text' => $comment]);
        } catch (GuzzleException $e) {
            throw  new TrelloAPIException($e->getMessage());
        }

    }

}
