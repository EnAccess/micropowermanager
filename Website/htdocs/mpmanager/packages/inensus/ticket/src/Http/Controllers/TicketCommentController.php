<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 26.09.18
 * Time: 15:21
 */

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\Ticket\Services\TicketCommentService;
use Inensus\Ticket\Trello\Comments;


class TicketCommentController extends Controller
{

    public function __construct(private TicketCommentService $ticketCommentService) {

    }

    public function store(Request $request)
    {
        $cardId = $request->input('cardId');
        $fullName = $request->input('fullName');
        $username = $request->input('username');
        $comment = $request->input('comment');
        // put all data together since trello uses api key to identify the user who commented a card.
        $commentData = $fullName . ' ' . $username . ': ' . $comment;

        $this->ticketCommentService->createComment($cardId, $comment);
    }


}
