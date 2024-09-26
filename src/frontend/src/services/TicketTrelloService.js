import { ErrorHandler } from "@/Helpers/ErrorHander"
import TicketTrelloRepository from "@/repositories/TicketTrelloRepository"

export class TicketTrelloService {
  constructor() {
    this.repository = TicketTrelloRepository
    this.ticket = {
      created: null,
      id: null,
      name: null,
      description: null,
      due: null,
      closed: null,
      lastActivity: null,
      comments: [],
      category: null,
      owner: [],
      assigned: null,
    }
  }

  fromJson(ticketData) {
    ticketData = ticketData.data
    let ticket = ticketData.ticket
    let actions = ticketData.actions

    if (ticket === null) {
      return null
    }
    this.ticket = {
      created: ticketData.created_at,
      id: ticket.id,
      name: ticket.name,
      description: ticket.desc,
      due: ticket.due,
      closed: ticketData.status === 1,
      lastActivity: ticket.dateLastActivity,
      comments: [],
      category: ticketData.category,
      owner: ticketData.owner,
      assigned: ticketData.assigned_to,
    }

    for (let i = 0; i < actions.length; i++) {
      let action = actions[i]
      if (action.type !== "commentCard") {
        continue
      }

      this.ticket.comments.push({
        comment: action.data.text,
        date: action.date,
        fullName: action.memberCreator.fullName,
        username: action.memberCreator.username,
      })
    }
    return this.ticket
  }

  commentCount() {
    return this.ticket.comments.length
  }

  async getTicketDetail(ticketData) {
    try {
      let response = await this.repository.detail(ticketData.ticket_id)
      if (response.status === 200) {
        return this.fromJson(response.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
