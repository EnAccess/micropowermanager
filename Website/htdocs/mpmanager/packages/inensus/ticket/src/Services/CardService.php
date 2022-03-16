<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 23.08.18
 * Time: 14:40
 */

namespace Inensus\Ticket\Services;


use Inensus\Ticket\Models\Board;
use Inensus\Ticket\Models\Card;
use Inensus\Ticket\Trello\Lists;
use function config;

class CardService
{
    private $lists;

    public function __construct(Lists $lists)
    {
        $this->lists = $lists;
    }

    public function initalizeList(Board $board)
    {
        $card = Card::where('status', 1)->first() ?? $this->saveCard($this->createCard($board));
        return $card;
    }


    private function createCard(Board $board, $name = null)
    {
        $name = $name ?? config('tickets.card_prefix');
        $name .= time();
        return $this->lists->createList($name, $board);
    }

    private function saveCard($cardData)
    {
        $card = new Card();
        $card->card_id = $cardData->id;
        $card->status = 1;
        $card->save();
        return $card;
    }
}
