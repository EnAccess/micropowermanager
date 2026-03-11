import { Paginator } from "@/Helpers/Paginator.js"
import { convertObjectKeysToCamelCase } from "@/Helpers/Utils.js"
import { resources } from "@/resources.js"

export class ApplianceLogService {
  constructor(appliancePersonId) {
    this.appliancePersonId = appliancePersonId
    this.logsList = []

    this.paginator = new Paginator(
      `${resources.appliances.person.logs}${appliancePersonId}/logs`,
    )
    this.paginator.perPage = 10
  }

  updateLogsList(logsData) {
    this.logsList = logsData.map((log) => {
      return convertObjectKeysToCamelCase(log)
    })
    return this.logsList
  }
}
