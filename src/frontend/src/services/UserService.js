import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { Paginator } from "@/Helpers/Paginator"
import UserRepository from "@/repositories/UserRepository"

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
      cityId: null,
    }
  }
  fromJson(user) {
    this.user = {
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.address_details ? user.address_details.phone : null,
      street: user.address_details ? user.address_details.street : null,
      cityId:
        user.address_details && user.address_details.city
          ? user.address_details.city.id
          : null,
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
  async create() {
    try {
      const { data, status, error } = await this.repository.create(this.user)
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
      phone: this.user.phone,
      street: this.user.street,
      city_id: this.user.cityId,
      name: this.user.name,
    }
    try {
      const { data, status, error } = await this.repository.put(userDataPm)
      if (!status === 200) {
        return new ErrorHandler(error, "http", status)
      }
      this.resetUser()
      return this.fromJson(data.data)
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  resetUser() {
    this.user = {
      id: null,
      name: null,
      email: null,
      phone: null,
      street: null,
      city_id: null,
    }
  }
}
