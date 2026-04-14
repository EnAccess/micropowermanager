import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import UserPermissionImportRepository from "@/repositories/UserPermissionImportRepository.js"

export class UserPermissionImportService {
  constructor() {
    this.repository = UserPermissionImportRepository
  }

  async import(data) {
    try {
      const {
        data: responseData,
        status,
        error,
      } = await this.repository.importUserPermissions(data)
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
