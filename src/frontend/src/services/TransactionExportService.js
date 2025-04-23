import TransactionExportRepository from "@/repositories/TransactionExportRepository"

export class TransactionExportService {
  constructor() {
    this.repository = TransactionExportRepository
  }

  async exportTransactions(payload) {
    const queryParameters = []
    for (const key in payload) {
      // eslint-disable-next-line no-prototype-builtins
      if (payload.hasOwnProperty(key) && payload[key] !== null) {
        queryParameters.push(
          `${encodeURIComponent(key)}=${encodeURIComponent(payload[key])}`,
        )
      }
    }
    const slug = queryParameters.join("&")
    return this.repository.download(slug)
  }
}
