import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SettingsImportRepository from "@/repositories/SettingsImportRepository"

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
      if (e.exception) {
        throw e
      }
      const errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }
}
