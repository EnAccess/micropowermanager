import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import VendingProviderRepository from "@/repositories/VendingProviderRepository.js"

export class VendingProviderService {
  constructor() {
    this.repository = VendingProviderRepository
    this.list = []
  }

  async getForMeter(meterId) {
    try {
      const { data, status, error } =
        await this.repository.listForMeter(meterId)
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.list = data.data

      return this.list
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  nameOf(key) {
    return this.list.find((provider) => provider.key === key)?.name ?? key
  }
}
