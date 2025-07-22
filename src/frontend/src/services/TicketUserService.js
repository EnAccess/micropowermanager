import { ErrorHandler } from "@/Helpers/ErrorHandler"
import TicketUserRepository from "@/repositories/TicketUserRepository"

export class TicketUserService {
  constructor() {
    this.repository = TicketUserRepository
    this.list = []
    this.newUser = {
      name: "",
      phone: "",
    }
  }

  async getUsers() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        let users = response.data.data
        this.list = users.map(this.pushUsers)
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getAvailableUsers() {
    try {
      const response = await this.repository.getAvailableUsers()
      if (response.status === 200) {
        this.availableUserList = response.data.data
      } else {
        new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      new ErrorHandler(e.response.data.message)
    }
  }

  pushUsers(user) {
    return {
      id: user.id,
      name: user.name,
      isTicketingUser: user.relation_ticket_user !== null,
      created_at: user.relation_ticket_user
        ? user.relation_ticket_user.created_at
        : "-",
    }
  }
  async createExternalUser(name, phone) {
    try {
      const user = {
        username: name,
        phone: phone,
      }

      let response = await this.repository.createExternal(user)
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

  resetNewUser() {
    this.newUser = {
      name: "",
      phone: "",
    }
  }
}
