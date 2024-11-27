<?php

namespace Inensus\Ticket\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inensus\Ticket\Services\TicketCommentService;
use Inensus\Ticket\Services\TicketUserService;

class TicketCommentController extends Controller {
    public function __construct(private TicketCommentService $ticketCommentService, private TicketUserService $ticketUserService) {}

    public function store(Request $request) {
        /** @var User $user */
        $user = Auth::user();
        $ticketId = (int) $request->input('cardId');
        $comment = $request->input('comment');

        $ticketUser = $this->ticketUserService->findOrCreateByUser($user);

        $this->ticketCommentService->createComment($ticketId, $comment, $ticketUser->getId());
    }
}
