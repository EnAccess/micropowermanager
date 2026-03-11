import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import ApplianceImportRepository from "@/repositories/ApplianceImportRepository.js"

export class ApplianceImportService {
  constructor() {
    this.repository = ApplianceImportRepository
  }

  async import(data) {
    try {
      const {
        data: responseData,
        status,
        error,
      } = await this.repository.importAppliances(data)
      if (status === 202) {
        return { async: true, jobId: responseData.data.job_id }
      }
      if (status !== 200 && status !== 201) {
        return new ErrorHandler(error || "Import failed", "http", status)
      }
      return responseData.data
    } catch (e) {
      if (e.exception) {
        throw e
      }
      const errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }
}
