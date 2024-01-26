import Repository from '../repositories/RepositoryFactory'

export class TransactionExportService {
    constructor () {
        this.repository = Repository.get('transactionExport')
    }

    exportTransactions (email, payload) {
        const queryParameters = [];
        for (const key in payload) {
            if (payload.hasOwnProperty(key) && payload[key] !== null) {
                queryParameters.push(`${encodeURIComponent(key)}=${encodeURIComponent(payload[key])}`);
            }
        }
        const slug = queryParameters.join('&');
        return this.repository.download(email, slug)
    }
}
