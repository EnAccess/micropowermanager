import { ErrorHandler } from "@/Helpers/ErrorHander"
import RestrictionRepository from "@/repositories/RestrictionRepository"

export class RestrictionService {
  constructor() {
    this.repository = RestrictionRepository
  }

  async sendPurchaseCode(purchaseCode, email) {
    try {
      let code_PM = {
        purchaseCode: purchaseCode,
        email: email,
      }
      let response = await this.repository.sendCode(code_PM)

      if (response.status === 200) {
        return response.data[0]
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e, "http")
    }
  }

  async purchaseCodeIsValid(purchaseCode, productCode, Type) {
    let restriction_PM = {
      token: purchaseCode,
      product_id: productCode,
      type: Type,
    }
    try {
      let response = await this.repository.check(restriction_PM)
      if (response.status === 200 || response.status === 201) {
        return true
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
