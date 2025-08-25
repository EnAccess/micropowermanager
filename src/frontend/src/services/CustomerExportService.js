import CustomerExportRepository from "@/repositories/CustomerExportRepository"

export class CustomerExportService {
  constructor() {
    this.repository = CustomerExportRepository
  }

  async exportCustomers(payload) {
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
