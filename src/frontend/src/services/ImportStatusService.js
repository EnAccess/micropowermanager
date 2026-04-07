import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import ImportStatusRepository from "@/repositories/ImportStatusRepository.js"

export class ImportStatusService {
  constructor() {
    this.repository = ImportStatusRepository
  }

  async getStatus(jobId) {
    try {
      const { data: responseData, status } =
        await this.repository.getStatus(jobId)
      if (status !== 200) {
        return new ErrorHandler("Failed to get import status", "http", status)
      }
      return responseData.data
    } catch (e) {
      const errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }
}
