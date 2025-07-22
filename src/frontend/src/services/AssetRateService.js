import { ErrorHandler } from "@/Helpers/ErrorHandler"

import AssetRateRepository from "@/repositories/AssetRateRepository"

export class AssetRateService {
  constructor() {
    this.repository = AssetRateRepository
  }

  async editAssetRate(rate, adminId, personId) {
    try {
      let terms = {
        newCost: rate.tempCost,
        cost: rate.rate_cost,
        admin_id: adminId,
        person_id: personId,
      }

      let response = await this.repository.update(rate.id, terms)

      if (response.status === 200 || response.status === 201) {
        return response
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
