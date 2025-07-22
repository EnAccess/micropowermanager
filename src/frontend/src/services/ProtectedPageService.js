import { ErrorHandler } from "@/Helpers/ErrorHandler"
import ProtectedPageRepository from "@/repositories/ProtectedPageRepository"

export class ProtectedPageService {
  constructor() {
    this.repository = ProtectedPageRepository
  }

  async getProtectedPages() {
    try {
      const { data, status, error } = await this.repository.list()
      if (status !== 200) {
        throw new ErrorHandler(error, "http", status)
      }
      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      throw new ErrorHandler(errorMessage, "http")
    }
  }

  async compareProtectedPagePassword(id, password) {
    try {
      const { data, status, error } = await this.repository.compare({
        id,
        password,
      })
      if (status !== 200) {
        throw new ErrorHandler(error, "http", status)
      }
      return data.result
    } catch (e) {
      const errorMessage = e.response.data.message
      throw new ErrorHandler(errorMessage, "http")
    }
  }
}
