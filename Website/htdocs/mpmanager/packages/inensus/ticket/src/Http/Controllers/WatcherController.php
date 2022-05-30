<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 04.10.18
 * Time: 16:09
 */

namespace Inensus\Ticket\Http\Controllers;

use App\Models\History;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Models\Ticket;
use function in_array;

/**
 * Trello notifies the controller on all changes on the registered boards
 * This class is responsible for filtering the actions and storing the needed ones like comments etc.
 * The reason to storing the actions, is to make a timeline.
 * Class WatcherController
 * @package Inensus\Ticket\Trello
 */
class WatcherController
{
    //TODO: fix for Saas.

    /**
     * The Trello action codes which are logged in our system.
     */
    public const INTERESTING_ACTIONS = ['createCard', 'addMemberToCard', 'commentCard', 'updateCard'];
    /**
     * @var Ticket
     */
    private $ticket;

    /**
     * WatcherController constructor.
     * @param Ticket $ticket
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function store(): void
    {
        //convert the json string
        $content = json_decode(request()->getContent(), true);
        if ($content === null) { //request body is not a valid json string.
            Log::debug('returning empty json');
            return;
        }
        //get the action type
        $action = $content['action'];
        $actionType = $action['type'];

        if (!$this->filterRequest($actionType)) { // there is no interest for that action
            Log::debug('action is not interesting');
            return;
        }

        $this->fetchAndStore($actionType, $action);
    }

    /**
     * Checks if the request type is interesting or not
     * @param string $type the request type
     * @return bool
     */
    private function filterRequest($type): bool
    {
        return in_array($type, self::INTERESTING_ACTIONS, false);
    }

    /**
     * Tries to match the given ticket id with an entry in the database
     * it should prevent, that any new ticket created directly in Trello
     * @param string $ticketId
     * @throws  ModelNotFoundException
     * @return Ticket
     */
    private function ticketMatcher(string $ticketId): Ticket
    {
        return $this->ticket->where('ticket_id', $ticketId)->firstOrFail();
    }

    private function fetchAndStore($type, $action): void
    {
        $text = ''; //initialize text
        $ticketId = $action['data']['card']['id']; //the affected card.
        $actionType = null;
        try {
            $ticket = $this->ticketMatcher($ticketId);
        } catch (ModelNotFoundException $e) {
            //Todo:  say Trello to delete that ticket via API call
            Log::debug('Ticket not found', [$e->getMessage()]);
            return;
        }

        $ticketTitle = $action['data']['card']['name'];
        $triggerPerson = $action['memberCreator']['fullName'];

        switch ($type) {
            case 'addMemberToCard': // a new user assigned to the card.
                $updatedFiled = 'member';
                $memberName = $action['data']['member']['name'];
                $text = $triggerPerson . ' added ' . $memberName . ' to the ticket "' . $ticketTitle . '"';
                $actionType = History::ACTION_UPDATE;
                break;
            case 'commentCard':
                $updatedFiled = 'comment';
                $text = $triggerPerson . ' commented ' . $ticketTitle . ' : ' . $action['data']['text'];
                $actionType = History::ACTION_UPDATE;
                break;
            case 'removeMemberFromCard':
                $memberName = $action['data']['member']['name'];
                $text = $triggerPerson . ' removed ' . $memberName . ' from the ticket "' . $ticketTitle . '"';
                $actionType = History::ACTION_UPDATE;
                break;
            case 'updateCard':
                $updatedFiled = key($action['data']['old']);
                $updatedValue = $action['data']['card'][$updatedFiled];
                if ($updatedFiled === 'closed') { //ticket is been closed
                    $text = $triggerPerson . ($updatedValue !== true ? ' closed' : 'reopened') . ' the ticket :' . $ticketTitle;
                    // change status in database too
                    $ticket->status = $updatedValue === true ? Ticket::STATUS['closed'] : Ticket::STATUS['opened'];
                    $ticket->save();
                } elseif ($updatedFiled === 'name') {
                    $text = $triggerPerson . ' renamed ticket to. ' . $updatedValue;
                } elseif ($updatedFiled === 'desc') {
                    $text = $triggerPerson . ' changed the description of ' . $ticketTitle . ' to ' . $updatedValue;
                } elseif ($updatedFiled === 'due') {
                    $text = $triggerPerson . ' changed the due date of ' . $ticketTitle . ' to ' . $updatedValue;
                }
                $actionType = History::ACTION_UPDATE;
                break;
            case 'createCard':
                $text = $triggerPerson . ' create' . $ticketTitle;
                $actionType = History::ACTION_CREATED;
                break;
        }

        //save history to the database via event
        event('history.create', [$ticket, $text, $actionType, $updatedFiled ?? null]);
    }
}
