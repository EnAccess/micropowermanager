import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SmsGatewayRepository from "@/repositories/SmsGatewayRepository"

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
        id: gateway.id,
        name: gateway.name,
        label: gateway.label,
        isActive: gateway.is_active,
      }))
      return this.availableGateways
    } catch (e) {
      const errorMessage = e.response?.data?.message || e.message
      throw new ErrorHandler(errorMessage, "http")
    }
  }
}
