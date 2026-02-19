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
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http", e.response.status)
    }
  }

  async checkPaymentStatus(transactionId) {
    try {
      const { data, status, error } =
        await this.repository.checkStatus(transactionId)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http", e.response?.status || 500)
    }
  }

  async pollPaymentStatus(transactionId, options = {}) {
    const { maxAttempts = 30, interval = 1000, onProgress = null } = options

    for (let attempt = 0; attempt < maxAttempts; attempt++) {
      const result = await this.checkPaymentStatus(transactionId)

      if (result instanceof ErrorHandler) {
        throw result
      }

      if (result.processed) {
        return result
      }

      if (onProgress) {
        onProgress(attempt + 1, maxAttempts)
      }

      if (attempt < maxAttempts - 1) {
        await new Promise((resolve) => setTimeout(resolve, interval))
      }
    }

    throw new ErrorHandler(
      "Payment processing timeout. Please check the payment status manually.",
      "timeout",
      408,
    )
  }
}
