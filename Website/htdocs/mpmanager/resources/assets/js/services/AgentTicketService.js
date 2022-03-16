import { TicketTrelloService } from './TicketTrelloService'
import { Paginator } from '../classes/paginator'
import { resources } from '../resources'

export class AgentTicketService {
    constructor (agentId) {
        this.trelloService = new TicketTrelloService()
        this.ticket = this.trelloService.ticket
        this.list = []
        this.paginator = new Paginator(resources.agents.tickets + '/' + agentId)
    }

    async updateList (data) {
        this.list = []
        for (let m in data) {
            let ticketData = await this.trelloService.getTicketDetail(data[m])
            ticketData.assignedTo = data[m].assigned_to
            this.list.push(ticketData)

        }
    }
}
