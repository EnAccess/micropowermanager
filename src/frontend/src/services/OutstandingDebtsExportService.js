import OutstandingDebtsExportRepository from "@/repositories/OutstandingDebtsExportRepository"

export class OutstandingDebtsExportService {
  constructor() {
    this.repository = OutstandingDebtsExportRepository
  }
  async exportOutstandingDebts() {
    return this.repository.download()
  }
}
