import { Paginator } from "@/Helpers/Paginator"
import { convertObjectKeysToCamelCase } from "@/Helpers/Utils"

export class ApplianceRatesLogsService {
  constructor(appliancePersonId) {
    this.appliancePersonId = appliancePersonId
    this.ratesList = []
    this.logsList = []

    // Create paginators for rates and logs
    this.ratesPaginator = new Paginator(
      `/api/appliances/person/${appliancePersonId}/rates`,
    )
    this.logsPaginator = new Paginator(
      `/api/appliances/person/${appliancePersonId}/logs`,
    )

    // Set custom per page values
    this.ratesPaginator.perPage = 15
    this.logsPaginator.perPage = 10
  }

  updateRatesList(ratesData) {
    this.ratesList = ratesData.map((rate) => {
      return convertObjectKeysToCamelCase(rate)
    })
    return this.ratesList
  }

  updateLogsList(logsData) {
    this.logsList = logsData.map((log) => {
      return convertObjectKeysToCamelCase(log)
    })
    return this.logsList
  }
}
