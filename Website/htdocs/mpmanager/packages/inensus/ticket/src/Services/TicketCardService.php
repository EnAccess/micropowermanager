<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 23.08.18
 * Time: 14:40
 */

namespace Inensus\Ticket\Services;



use Inensus\Ticket\Models\TicketBoard;
use Inensus\Ticket\Models\TicketCard;
use Inensus\Ticket\Trello\Lists;
use function config;

class TicketCardService
{

    public function __construct(private TicketCard $ticketCard, private Lists $lists)
    {
    }

    public function initalizeList(TicketBoard $board)
    {
        return $this->ticketCard->newQuery()->where('status', 1)->first()
            ?? $this->saveCard($this->createCard($board));
    }

    private function createCard(TicketBoard $board, $name = null)
    {
        $name = $name ?? config('tickets.card_prefix');
        $name .= time();
        return $this->lists->createList($name, $board);
    }

    private function saveCard($cardData)
    {

        return $this->ticketCard->newQuery()->create([
            'card_id' => $cardData->id,
            'status' => 1
        ]);
    }
}
