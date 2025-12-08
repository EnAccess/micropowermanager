import { convertObjectKeysToCamelCase } from "@/Helpers/Utils"
import EcreeeTokenRepository from "../repositories/EcreeeTokenRepository"
import { ErrorHandler } from "@/Helpers/ErrorHandler"

export class EcreeeTokenService {
  constructor() {
    this.repository = EcreeeTokenRepository
    this.ecreeeToken = {
      token: null,
      isActive: null,
    }
  }

  async activateToken() {
    try {
      let tokenData = {}
      if (this.ecreeeToken.token) {
        const { data, status, error } = await this.repository.update(
          this.ecreeeToken.id,
        )
        if (status !== 200 && status !== 201)
          return new ErrorHandler(error, "http", status)
        tokenData = convertObjectKeysToCamelCase(data.data)
      } else {
        const { data, status, error } = await this.repository.create()
        if (status !== 200 && status !== 201)
          return new ErrorHandler(error, "http", status)
        tokenData = convertObjectKeysToCamelCase(data.data)
      }

      this.ecreeeToken = tokenData
      return this.ecreeeToken
    } catch (e) {
      const errorMessage = e.response.data.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getToken() {
    try {
      const { data, status, error } = await this.repository.get()
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      this.ecreeeToken = convertObjectKeysToCamelCase(data.data)

      return this.ecreeeToken
    } catch (e) {
      const errorMessage = e.response.data.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
