import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { Paginator } from "@/Helpers/Paginator"
import { convertObjectKeysToCamelCase } from "@/Helpers/Utils"
import { resources } from "@/resources"

import ApplianceRateRepository from "@/repositories/ApplianceRateRepository"

export class ApplianceRateService {
  constructor(appliancePersonId = null) {
    this.repository = ApplianceRateRepository
    this.ratesList = []

    if (appliancePersonId) {
      this.paginator = new Paginator(
        `${resources.appliances.person.rates}${appliancePersonId}/rates`,
      )
      this.paginator.perPage = 15
    }
  }

  async editApplianceRate(rate, adminId, personId) {
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

  updateRatesList(ratesData) {
    this.ratesList = ratesData.map((rate) => {
      return convertObjectKeysToCamelCase(rate)
    })
    return this.ratesList
  }
}
