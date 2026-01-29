import { ErrorHandler } from "@/Helpers/ErrorHandler"
import UserPermissionImportRepository from "@/repositories/UserPermissionImportRepository"

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
