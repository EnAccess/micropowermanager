<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TicketCommentService;
use App\Services\TicketUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketCommentController extends Controller {
    public function __construct(private TicketCommentService $ticketCommentService, private TicketUserService $ticketUserService) {}

    public function store(Request $request): void {
        /** @var User $user */
        $user = Auth::user();
        $ticketId = (int) $request->input('cardId');
        $comment = $request->input('comment');

        $ticketUser = $this->ticketUserService->findOrCreateByUser($user);

        $this->ticketCommentService->createComment($ticketId, $comment, $ticketUser->getId());
    }
}
