import { Paginator } from "@/Helpers/Paginator"
import { convertObjectKeysToCamelCase } from "@/Helpers/Utils"
import { resources } from "@/resources"

export class ApplianceLogService {
  constructor(appliancePersonId) {
    this.appliancePersonId = appliancePersonId
    this.logsList = []

    // Create paginator for logs
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
