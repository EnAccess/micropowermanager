import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import SmsGatewayRepository from "@/repositories/SmsGatewayRepository.js"

export class SmsGatewayService {
  constructor() {
    this.repository = SmsGatewayRepository
    this.availableGateways = []
  }

  async getAvailableGateways() {
    try {
      const { data, status, error } =
        await this.repository.getAvailableGateways()
      if (status !== 200) throw new ErrorHandler(error, "http", status)
      this.availableGateways = data.data.map((gateway) => ({
        slug: gateway.slug,
        displayName: gateway.display_name,
      }))
      return this.availableGateways
    } catch (e) {
      const errorMessage = e.response?.data?.message || e.message
      throw new ErrorHandler(errorMessage, "http")
    }
  }
}
