import StsMeterVendRepository from "../repositories/StsMeterVendRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"

export class StsMeterVendService {
  constructor() {
    this.repository = StsMeterVendRepository
  }

  async getStatus() {
    try {
      const response = await this.repository.status()
      return response.data.data
    } catch (e) {
      return new ErrorHandler(
        e.response.data.message,
        "http",
        e.response.status,
      )
    }
  }

  async importKeys(file) {
    const formData = new FormData()
    formData.append("csv", file)
    try {
      const response = await this.repository.importKeys(formData)
      return response.data.data
    } catch (e) {
      return new ErrorHandler(
        e.response.data.message,
        "http",
        e.response.status,
      )
    }
  }

  async downloadKeyTemplate() {
    try {
      return await this.repository.keyTemplate()
    } catch (e) {
      return new ErrorHandler(e.message, "http", e.response?.status)
    }
  }
}
