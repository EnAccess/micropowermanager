import { ErrorHandler } from "@/Helpers/ErrorHandler"
import StatusRepository from "../repositories/StatusRepository"

export class StatusService {
  constructor() {
    this.repository = StatusRepository
    this.status = {}
  }

  async getMeterStatus(meterId) {
    try {
      const response = await this.repository.show(meterId)
      if (response.status === 200) {
        this.status = response.data.data.attributes
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async changeMeterStatus(meterId, status) {
    try {
      const statusPM = {
        status: status === true ? "ON" : "OFF",
        meterId: meterId,
      }
      const response = await this.repository.update(statusPM)
      if (response.status === 200) {
        this.status = response.data.data.attributes
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
