import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"
import TicketRepository from "@/repositories/TicketRepository.js"
import { resources } from "@/resources.js"

export class Ticket {
  static listFromJson(data) {
    if (!Array.isArray(data)) {
      return []
    }
    return data.map((ticket) => new Ticket().fromJson(ticket))
  }

  fromJson(ticketData) {
    let comments = ticketData?.comments
    this.created = ticketData.created_at
    this.id = ticketData.id
    this.title = ticketData.title
    this.description = ticketData.content
    this.due = ticketData.due_date
    this.category = ticketData.category?.label_name || "-"
    this.closed = ticketData.status === 1
    this.status = ticketData.status
    this.owner = ticketData.owner
    this.assignedTo = ticketData.assigned_to

    if (comments && Array.isArray(comments)) {
      const commentList = comments.map(function (comment) {
        return {
          comment: comment.comment,
          date: comment.created_at,
          username: comment.ticket_user?.user_name || "Unknown",
        }
      })
      this.comments = commentList
    } else {
      this.comments = []
    }

    return this
  }
}

export class TicketList {
  constructor(resource) {
    this.list = []
    this.paginator = new Paginator(resource)
  }

  updateList(data) {
    this.list = Ticket.listFromJson(data)
  }
}

export class TicketService {
  constructor() {
    this.repository = TicketRepository
    this.categories = []
    this.openedList = []
    this.closedList = []
    this.openedPaginator = new Paginator(resources.ticket.list + "?status=0")
    this.closedPaginator = new Paginator(resources.ticket.list + "?status=1")
  }

  updateList(data, type) {
    const tickets = Ticket.listFromJson(data)

    if (type === "ticketListOpened") this.openedList = tickets
    else this.closedList = tickets
  }

  async getCategories() {
    try {
      let response = await this.repository.listCategory()
      if (response.status === 200 || response.status === 201) {
        this.categories = response.data.data
        return this.categories
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async createMaintenanceTicket(maintenanceData) {
    let maintenanceDataPM = {
      creator: maintenanceData.creator,
      dueDate: maintenanceData.dueDate,
      label: maintenanceData.category,
      outsourcing: maintenanceData.amount,
      description: maintenanceData.description,
      title: maintenanceData.title,
      owner_id: maintenanceData.assigned.id,
      owner_type: "person",
      creator_type: "admin",
    }
    try {
      let response = await this.repository.create(maintenanceDataPM)
      if (response.status === 200 || response.status === 201) {
        return response.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async closeTicket(id) {
    try {
      let response = await this.repository.close(id)

      if (response.status === 200 || response.status === 201) {
        const ticket = new Ticket().fromJson(response.data.data)
        ticket.closed = true
        return ticket
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
