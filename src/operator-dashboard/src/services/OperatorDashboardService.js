import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import OperatorDashboardRepository from "@/repositories/OperatorDashboardRepository.js"
import {
  mapPlatform,
  mapTenantDetail,
} from "@/services/OperatorDashboardMapper.js"

export class OperatorDashboardService {
  constructor() {
    this.repository = OperatorDashboardRepository
  }

  async platform() {
    try {
      const response = await this.repository.platform()

      return mapPlatform(this.responseValidator(response))
    } catch (e) {
      return this.errorHandler(e)
    }
  }

  async tenant(companyId) {
    try {
      const response = await this.repository.tenant(companyId)

      return mapTenantDetail(this.responseValidator(response))
    } catch (e) {
      return this.errorHandler(e)
    }
  }

  async refresh() {
    try {
      const response = await this.repository.refresh()

      return this.responseValidator(response, [200, 202])
    } catch (e) {
      return this.errorHandler(e)
    }
  }

  async downloadInvoice(companyId, month) {
    try {
      const response = await this.repository.invoice(companyId, month)
      const contentDisposition = response.headers["content-disposition"] || ""
      const filename =
        contentDisposition.split("filename=")[1]?.replace(/['"]/g, "") ||
        `invoice-data_${month}.csv`

      return { blob: response.data, filename }
    } catch (e) {
      return this.errorHandler(await this.readBlobErrorBody(e))
    }
  }

  /**
   * A blob request's error body arrives as a Blob too, so its JSON message has
   * to be read before the error handler can show it.
   */
  async readBlobErrorBody(e) {
    if (e?.response?.data instanceof Blob) {
      try {
        e.response.data = JSON.parse(await e.response.data.text())
      } catch {
        e.response.data = {}
      }
    }

    return e
  }

  responseValidator(response, expectedStatus = [200]) {
    return expectedStatus.includes(response.status)
      ? response.data.data
      : new ErrorHandler(response.error, "http", response.status)
  }

  /**
   * A failed request has no response at all when the network or CORS rejected it,
   * so the message has to be read defensively.
   */
  errorHandler(e) {
    if (e && e.exception) {
      throw e.exception
    }

    return new ErrorHandler(
      e?.response?.data?.message ?? e?.message ?? "Request failed",
      "http",
      e?.response?.status,
    )
  }
}
