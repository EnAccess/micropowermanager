import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils"
import AppliancePaymentRepository from "@/repositories/AppliancePaymentRepository"

export class AppliancePaymentService {
  constructor() {
    this.repository = AppliancePaymentRepository
  }

  async getPaymentForAppliance(applianceId, payment) {
    const paymentParams = convertObjectKeysToSnakeCase(payment)
    try {
      const { data, status, error } = await this.repository.update(
        applianceId,
        paymentParams,
      )
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message[0]
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
