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

  async refresh(companyId = null) {
    try {
      const response =
        companyId === null
          ? await this.repository.refresh()
          : await this.repository.refreshTenant(companyId)

      return this.responseValidator(response, [200, 202])
    } catch (e) {
      return this.errorHandler(e)
    }
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
