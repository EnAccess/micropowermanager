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

  async importKeys(file, { manufacturerId, tariffId } = {}) {
    const formData = new FormData()
    formData.append("csv", file)
    if (manufacturerId) formData.append("manufacturer_id", manufacturerId)
    if (tariffId) formData.append("tariff_id", tariffId)
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
