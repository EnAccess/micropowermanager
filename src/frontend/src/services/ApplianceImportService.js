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
      if (e.message && e.type) {
        throw e
      }
      const errorMessage = e.response?.data?.message || e.message
      const error = {
        message: errorMessage,
        type: "http",
        status_code: e.response?.status,
      }
      if (e.response?.data?.errors) {
        error.errors = e.response.data.errors
      }
      throw error
    }
  }
}
