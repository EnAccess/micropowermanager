import { Paginator } from "@/Helpers/Paginator"
import { resources } from "@/resources"
import TicketOursourcePayoutReportsRepository from "@/repositories/TicketOursourcePayoutReportsRepository"

export class TicketOursourcePayoutReportsService {
  constructor() {
    this.repository = TicketOursourcePayoutReportsRepository
    this.list = []
    this.paginator = new Paginator(resources.ticketOursourcePayoutReports.list)
  }

  updateList(ticketOutsourcePayoutReports) {
    for (let index in ticketOutsourcePayoutReports) {
      let ticketOutsourcePayoutReport = {
        id: ticketOutsourcePayoutReports[index].id,
        date: ticketOutsourcePayoutReports[index].date,
        path: ticketOutsourcePayoutReports[index].path,
      }
      this.list.push(ticketOutsourcePayoutReport)
    }
    return this.list
  }

  exportTicketOutsourcePayoutReport(id) {
    return this.repository.download(id)
  }

  showAll() {}
}
