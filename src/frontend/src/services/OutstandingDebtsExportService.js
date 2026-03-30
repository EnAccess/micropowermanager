import OutstandingDebtsExportRepository from "@/repositories/OutstandingDebtsExportRepository.js"

export class OutstandingDebtsExportService {
  constructor() {
    this.repository = OutstandingDebtsExportRepository
  }
  async exportOutstandingDebts() {
    return this.repository.download()
  }
}
