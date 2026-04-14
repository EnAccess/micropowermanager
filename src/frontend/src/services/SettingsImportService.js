import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import SettingsImportRepository from "@/repositories/SettingsImportRepository.js"

export class SettingsImportService {
  constructor() {
    this.repository = SettingsImportRepository
  }

  async import(data) {
    try {
      const {
        data: responseData,
        status,
        error,
      } = await this.repository.importSettings(data)
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
