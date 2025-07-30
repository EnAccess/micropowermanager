import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MiniGridDeviceRepository from "@/repositories/MiniGridDeviceRepository"

export class MiniGridDeviceService {
  constructor() {
    this.repository = MiniGridDeviceRepository
    this.list = []
  }

  async getMiniGridDevices(miniGridId) {
    try {
      const { data, status, error } = await this.repository.list(miniGridId)
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.list = data.data

      return data.data
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
