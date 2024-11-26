<?php

namespace Inensus\Ticket\Services;

use App\Models\Person\Person;
use Inensus\Ticket\Models\TicketComment;

class TicketCommentService {
    public function __construct(
        private Person $person,
        private TicketComment $ticketComment,
        private TicketUserService $ticketUserService,
    ) {}

    public function createComment(int $ticketId, string $comment, int $ticketUserId): TicketComment {
        $commentData = [
            'comment' => $comment,
            'ticket_id' => $ticketId,
            'ticket_user_id' => $ticketUserId,
        ];

        /** @var TicketComment $comment */
        $comment = $this->ticketComment->newQuery()->create($commentData);

        return $comment;
    }

    // store a comment if the sender is an maintenance guy  and responds with sms to an open ticket.
    public function storeComment($sender, $message) {
        $person = $this->person::with([
            'addresses',
            'tickets' => static function ($q) {
                $q->where('status', 0)->latest()->limit(1);
            },
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
            $ticketUser = $this->ticketUserService->findByPhone($sender);
            $this->createComment($person->tickets[0]->ticket_id, 'Sms Comment'.$message, $ticketUser->getId());
        }
    }
}
