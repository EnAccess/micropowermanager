import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MiniGridDashboardCacheDataRepository from "@/repositories/MiniGridDashboardCacheDataRepository"

export class MiniGridDashboardCacheDataService {
  constructor() {
    this.repository = MiniGridDashboardCacheDataRepository
  }

  async list() {
    try {
      const response = await this.repository.list()
      return this.responseValidator(response)
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  async update(from = null, to = null) {
    try {
      const response = await this.repository.update(from, to)
      return this.responseValidator(response)
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  async detail(id) {
    try {
      const response = await this.repository.detail(id)
      return this.responseValidator(response)
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  responseValidator(response, expectedStatus = [200]) {
    return expectedStatus.includes(response.status)
      ? response.data.data
      : new ErrorHandler(response.error, "http", response.status)
  }
}
