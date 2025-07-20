import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MeterTypeRepository from "@/repositories/MeterTypeRepository"

export class MeterTypeService {
  constructor() {
    this.repository = MeterTypeRepository
    this.meterTypesList = []
  }

  prepareMeterType(meterType) {
    let meterTypeOnline = meterType.online === 1 ? "Online" : "Offline"
    let meterTypes = {
      id: meterType.id,
      name:
        meterType.max_current + "A " + meterType.phase + "P " + meterTypeOnline,
      max_current: meterType.max_current,
      online: meterType.online,
    }
    return meterTypes
  }

  async getMeterTypes() {
    try {
      this.meterTypesList = []
      let response = await this.repository.index()
      if (response.status === 200) {
        let data = response.data.data
        this.meterTypesList = data.map(this.prepareMeterType)
        return this.meterTypesList
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async createMeterType(meterType) {
    try {
      let response = await this.repository.store(meterType)
      if (response.status === 201) {
        return this.getMeterTypes()
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
