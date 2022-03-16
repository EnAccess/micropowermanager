<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 26.09.18
 * Time: 15:21
 */

namespace Inensus\Ticket\Http\Controllers;


use Inensus\Ticket\Services\CommentService;
use Inensus\Ticket\Trello\Comments;
use Request;

class CommentController extends Controller
{
    private $commentService;

    public function __construct(CommentService $service)
    {
        $this->commentService = $service;
    }

    public function store(Request $request)
    {
        $cardId = request('cardId');
        // put all data together since trello uses api key to identify the user who commented a card.
        $comment = request('fullName') .
            ' ' .
            request('username') .
            ': ' .
            request('comment');

        $this->commentService->createComment($cardId, $comment);
    }


}
