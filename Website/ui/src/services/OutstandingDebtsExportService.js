import OutstandingDebtsExportRepository from '@/repositories/OutstandingDebtsExportRepository'

export class OutstandingDebtsExportService {
    constructor() {
        this.repository = OutstandingDebtsExportRepository
    }
    exportOutstandingDebts(email) {
        return this.repository.download(email)
    }
}
