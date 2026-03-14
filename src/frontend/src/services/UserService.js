import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { Paginator } from "@/Helpers/Paginator.js"
import UserRepository from "@/repositories/UserRepository.js"

export class UserService {
  constructor() {
    this.repository = UserRepository
    this.paginator = new Paginator(resources.user.list)
    this.users = []
    this.selectedUser = null
    this.user = {
      id: null,
      name: null,
      email: null,
      phone: null,
      street: null,
      villageId: null,
      roles: [],
    }
  }
  fromJson(user) {
    this.user = {
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.address_details ? user.address_details.phone : null,
      street: user.address_details ? user.address_details.street : null,
      villageId:
        user.address_details && user.address_details.village
          ? user.address_details.village.id
          : null,
      roles: user.roles,
    }
    return this.user
  }
  updateList(users) {
    this.users = []
    for (let u in users) {
      this.users.push(this.fromJson(users[u]))
    }
    this.resetUser()
    return this.users
  }
  async list() {
    try {
      const { data, status } = await this.repository.list()
      if (status !== 200) {
        return new ErrorHandler("Failed", status)
      }
      this.users = data.data
      return this.users
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }
  async create(payload = {}) {
    try {
      const requestBody = { ...this.user, ...payload }
      const { data, status, error } = await this.repository.create(requestBody)
      if (status !== 200) {
        return new ErrorHandler(error, status)
      }
      this.resetUser()
      return data.data
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async get(id) {
    try {
      const { data, status } = await this.repository.get(id)
      if (status !== 200) {
        return new ErrorHandler("Failed", status)
      }
      return this.fromJson(data.data)
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async update() {
    const userDataPm = {
      id: this.user.id,
      name: this.user.name,
    }

    if (this.user.roles) {
      userDataPm.roles = this.user.roles
    }

    // Update user basic info
    try {
      const { status, error } = await this.repository.put(userDataPm)
      if (status !== 200) {
        return new ErrorHandler(error, "http", status)
      }
    } catch (e) {
      let errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http")
    }

    // Update address separately if needed
    if (this.user.phone || this.user.street || this.user.villageId) {
      const addressData = {
        id: this.user.id,
        name: this.user.name,
        phone: this.user.phone,
        street: this.user.street,
        village_id: this.user.villageId,
      }
      try {
        await this.repository.putAddress(addressData)
      } catch (e) {
        let errorMessage = e.response?.data?.message || e.message
        return new ErrorHandler(errorMessage, "http")
      }
    }

    this.resetUser()
    return this.user
  }
  resetUser() {
    this.user = {
      id: null,
      name: null,
      email: null,
      phone: null,
      street: null,
      villageId: null,
    }
  }
}
