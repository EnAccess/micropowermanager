<?php

namespace Inensus\Ticket\Interfaces;

interface ITicketProvider {
    // initializes the ticketing system
    public function initialize();

    // a list of all tickets
    public function getTicketList(bool $includeClosed);

    // to get a list of tickets which are about a special theme by fetching the title of the ticket.
    public function getPersonalizedTicketList($subject);

    // get all details from the given ticketIdentifier.
    public function getTicketDetail($ticketIdentifier);

    // creates a new un-ordered ticket
    public function createTicket($ticketData);

    // creates a personalized ticket. The title is the personalized key.
    public function createPersonalizedTicket($subject, $ticketData);

    // assigns a person to the ticket.
    public function assignPersonToTicket($person, $ticket);

    // assigns a label (done, in process, etc..) to the ticket
    public function assignLabelToTicket($label, $ticket);

    // overrides all other labels  with the given new label
    public function setLabelToTicket($label, $ticket);

    // closes a ticket
    public function closeTicket($ticketIdentifier);
}
