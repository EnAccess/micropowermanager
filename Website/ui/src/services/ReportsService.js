import { Paginator } from "@/Helpers/Paginator"
import { resources } from "@/resources"
import ReportsRepository from "@/repositories/ReportsRepository"

export class ReportsService {
  constructor() {
    this.repository = ReportsRepository
    this.report = {
      id: null,
      name: null,
      path: null,
      date: null,
      type: null,
    }
    this.list = []
    this.paginatorWeekly = new Paginator(resources.reports.weekly.list)
    this.paginatorMonthly = new Paginator(resources.reports.monthly.list)
  }

  updateList(reports) {
    this.list = reports.map((report) => {
      return {
        id: report.id,
        name: report.name,
        path: report.path,
        date: report.date,
        type: report.type,
      }
    })
    return this.list
  }

  exportReport(id, reference, companyId) {
    return this.repository.download(id, reference, companyId)
  }
}
