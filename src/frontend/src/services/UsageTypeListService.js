import { ErrorHandler } from "@/Helpers/ErrorHandler"
import UsageTypeRepository from "@/repositories/UsageTypeRepository"

export class UsageTypeListService {
  constructor() {
    this.repository = UsageTypeRepository
    this.list = []
  }

  async getUsageTypes() {
    try {
      this.list = []
      let response = await this.repository.list()
      if (response.status === 200 || response.status === 201) {
        this.list = response.data.data
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
