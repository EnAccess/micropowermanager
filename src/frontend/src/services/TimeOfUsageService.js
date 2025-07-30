import { ErrorHandler } from "@/Helpers/ErrorHandler"
import TimeOfUsageRepository from "@/repositories/TimeOfUsageRepository"

export class TimeOfUsageService {
  constructor() {
    this.repository = TimeOfUsageRepository
    this.tou = {
      id: null,
      tariffId: null,
      start: null,
      end: null,
      value: null,
    }
  }

  async deleteTou(id) {
    try {
      let response = await this.repository.delete(id)
      if (response.status === 200) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
