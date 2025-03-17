import { Paginator } from "@/Helpers/Paginator"
import { resources } from "@/resources"

export class AgentTicketService {
  constructor(agentId) {
    this.list = []
    this.paginator = new Paginator(resources.agents.tickets + "/" + agentId)
  }

  async updateList(data) {
    this.list = []
    this.list = data?.data?.map((ticket) => {
      return {
        created: ticket.created_at,
        id: ticket.id,
        name: ticket.name,
        description: ticket.content,
        due: ticket.due,
        closed: ticket.status === 1,
        lastActivity: null,
        comments: ticket.comments,
        category: ticket.category.label_name,
        owner: ticket.owner.name + ticket.owner.surname,
        assigned:
          ticket.assigned_id && ticket.assigned_to
            ? ticket.assigned_to.user_name
            : null,
        title: ticket.title,
      }
    })
  }
}
