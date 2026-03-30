import { Paginator } from "@/Helpers/Paginator.js"
import ReportsRepository from "@/repositories/ReportsRepository.js"
import { resources } from "@/resources.js"

export class ReportsService {
  constructor() {
    this.repository = ReportsRepository
    this.report = {
      id: null,
      name: null,
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
        date: report.date,
        type: report.type,
      }
    })
    return this.list
  }

  async exportReport(id) {
    return this.repository.download(id)
  }
}
