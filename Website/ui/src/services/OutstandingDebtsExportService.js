import Repository from '../repositories/RepositoryFactory'
export class OutstandingDebtsExportService {
    constructor() {
        this.repository = Repository.get('outstandingDebtsExportRepository')
    }
    exportOutstandingDebts(email) {
        return this.repository.download(email)
    }
}
