import { ErrorHandler } from "@/Helpers/ErrorHandler"

import AuthenticationRepository from "../repositories/AuthenticationRepository"

export class AuthenticationService {
  constructor() {
    this.repository = AuthenticationRepository
    this.authentication = {
      id: null,
      token: null,
    }
  }

  fromJson(authenticationData) {
    this.authentication = {
      id: authenticationData.id,
      token: authenticationData.token,
    }
    return this.authentication
  }

  async getAuthentication() {
    try {
      let response = await this.repository.get()
      if (response.status === 200) {
        return this.fromJson(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
