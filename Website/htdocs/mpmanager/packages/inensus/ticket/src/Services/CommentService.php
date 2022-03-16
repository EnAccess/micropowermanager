<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 26.09.18
 * Time: 16:19
 */

namespace Inensus\Ticket\Services;


use App\Models\Person\Person;
use Inensus\Ticket\Trello\Comments;

class CommentService
{

    private $comments;
    private $person;

    public function __construct(Comments $comments, Person $person)
    {
        $this->comments = $comments;
        $this->person = $person;
    }

    public function createComment($cardId, $comment)
    {
        return $this->comments->newComment($cardId, $comment);
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
