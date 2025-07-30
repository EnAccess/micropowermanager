import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { Paginator } from "@/Helpers/Paginator"
import { resources } from "@/resources"
import TicketRepository from "@/repositories/TicketRepository"

export class Ticket {
  constructor() {
    this.id = null
    this.name = null
    this.description = null
    this.due = null
    this.closed = null
    this.lastActivity = null
    this.comments = []
    this.category = null
    this.created_at = null
  }

  fromJson(ticketData) {
    let comments = ticketData?.comments
    this.created = ticketData.created_at
    this.id = ticketData.id
    this.title = ticketData.title
    this.description = ticketData.content
    this.due = ticketData.due_date
    this.category = ticketData.category.label_name
    this.closed = ticketData.status === 1
    this.status = ticketData.status

    if (comments) {
      const commentList = comments.map(function (comment) {
        return {
          comment: comment.comment,
          date: comment.created_at,
          username: comment.ticket_user.user_name,
        }
      })
      this.comments = commentList
    }

    return this
  }

  commentCount() {
    return this.comments.length
  }

  close() {
    axios
      .delete(resources.ticket.close, { data: { ticketId: this.id } })
      .then(() => {
        this.closed = true
      })
  }
}

export class UserTickets {
  constructor(personId) {
    this.list = []
    this.paginator = new Paginator(resources.ticket.getUser + personId)
  }

  addTicket(ticket) {
    this.list.push(ticket)
  }

  search() {
    // this.paginator = new Paginator(resources.meters.search);
    // EventBus.$emit('loadPage', this.paginator, {'term': term});
  }

  showAll() {
    //this.paginator = new Paginator(resources.meters.list);
    //EventBus.$emit('loadPage', this.paginator);
  }

  updateList(data) {
    this.list = []
    if ("data" in data) {
      this.list = data.data.map(function (ticket) {
        return new Ticket().fromJson(ticket)
      })
    }
  }

  newComment(commentData) {
    axios.post(resources.ticket.comments, commentData)
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

  async updateList(data, type) {
    if (type === "ticketListOpened") this.openedList = []
    else this.closedList = []

    const result = data?.data?.map((ticket) => {
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
    if (type === "ticketListOpened") this.openedList = result
    else this.closedList = result
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
      owner_type: "maintenance_user",
      creator_type: "admin",
    }
    try {
      let response = await this.repository.create(maintenanceDataPM)
      if (response.status === 200 || response.status === 201) {
        return response.data.data
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
