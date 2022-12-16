import { TicketTrelloService } from './TicketTrelloService'
import { Paginator } from '@/classes/paginator'
import { resources } from '@/resources'
import {Ticket} from "@/classes/person/ticket";

export class AgentTicketService {
    constructor (agentId) {
        this.trelloService = new TicketTrelloService()
        this.ticket = this.trelloService.ticket
        this.list = []
        this.paginator = new Paginator(resources.agents.tickets + '/' + agentId)
    }

    async updateList (data) {
        this.list = []
        console.log("data", data);
        debugger
        if(data && data.length>0) {
            const tickets = data?.data.map(function (ticket) {
                return (new Ticket()).fromJson(ticket)
            });
            this.list = tickets ?? [];
        } else {

        }
    }
}
