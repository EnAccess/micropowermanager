import { ErrorHandler } from "@/Helpers/ErrorHandler"

import CountryRespository from "@/repositories/CountryRespository"

export default class CountryService {
  constructor() {
    this.repository = CountryRespository
    this.list = []
  }

  async getCountries() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        this.list = response.data.data
        return this.list
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
