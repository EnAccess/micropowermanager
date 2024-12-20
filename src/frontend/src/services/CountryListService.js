import { ErrorHandler } from "@/Helpers/ErrorHandler"
import CountriesRepository from "@/repositories/CountriesRepository"

export class CountryListService {
  constructor() {
    this.repository = CountriesRepository
    this.countryList = []
  }

  async list() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        this.countryList = response.data.data
        return this.countryList
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }
}
