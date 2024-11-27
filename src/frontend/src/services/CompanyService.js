import { ErrorHandler } from "@/Helpers/ErrorHander"
import CompanyRepository from "@/repositories/CompanyRepository"

export class CompanyService {
  constructor() {
    this.repository = CompanyRepository
  }

  async register(company) {
    try {
      let response = await this.repository.create(company)
      if (response.status === 200 || response.status === 201) {
        return response.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response?.data.message
      return new ErrorHandler(errorMessage, "http", e.response?.status)
    }
  }

  async getCompanyByUser(user) {
    try {
      let response = await this.repository.get(user)
      if (response.status === 200 || response.status === 201) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }
}
