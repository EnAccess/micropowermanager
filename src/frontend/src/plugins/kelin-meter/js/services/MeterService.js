import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MeterRepository from "@/repositories/MeterRepository"

export class MeterService {
  constructor() {
    this.repository = MeterRepository
    this.list = []
    this.isSync = false
    this.pagingUrl = "/api/kelin-meters/kelin-meter"
    this.routeName = "/kelin-meters/kelin-meter"
    this.meter = {
      id: null,
      terminalId: null,
      meterName: null,
      meterAddress: null,
      owner: null,
    }
  }
  updateList(responseData) {
    this.list = []
    for (let data of responseData) {
      this.list.push(data.data.attributes)
    }
  }
  async syncMeters() {
    try {
      const response = await this.repository.sync()
      if (response.status === 200) {
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async checkMeters() {
    try {
      const response = await this.repository.syncCheck()
      if (response.status === 200) {
        return response.data.data.result
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
