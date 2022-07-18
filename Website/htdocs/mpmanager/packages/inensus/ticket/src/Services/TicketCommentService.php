<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 26.09.18
 * Time: 16:19
 */

namespace Inensus\Ticket\Services;


use App\Exceptions\TrelloAPIException;
use App\Models\Person\Person;
use App\Services\IBaseService;
use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Trello\Comments;

class TicketCommentService
{

    public function __construct(private Comments $commentsGateway, private Person $person)
    {

    }

    public function createComment($cardId, $comment)
    {
        try {
            return $this->commentsGateway->newComment($cardId, $comment);
        } catch (TrelloAPIException $exception) {
            Log::error('An unexpected error occurred at creating comment in trello API.',
                ['message' => $exception->getMessage()]);

            throw new TrelloAPIException($exception->getMessage());
        }

    }

    // store a comment if the sender is an maintenance guy  and responds with sms to an open ticket.
    public function storeComment($sender, $message)
    {
        $person = $this->person::with([
            'addresses',
            'tickets' => static function ($q) {
                $q->where('status', 0)->latest()->limit(1);
            }
        ])
            ->whereHas(
                'addresses',
                static function ($q) use ($sender) {
                    $q->where('phone', $sender);
                }
            )
            ->where('is_customer', 0)
            ->first();
        if ($person && !$person->tickets->isEmpty()) {
            $this->createComment($person->tickets[0]->ticket_id, 'Sms Comment' . $message);
        }
    }


}
