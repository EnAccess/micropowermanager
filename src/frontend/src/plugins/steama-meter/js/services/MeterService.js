import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MeterRepository from "../repositories/MeterRepository"

export class MeterService {
  constructor() {
    this.repository = MeterRepository
    this.list = []
    this.isSync = false
    this.count = 0
    this.pagingUrl = "/api/steama-meters/steama-meter"
    this.routeName = "/steama-meters/steama-meter"
    this.meter = {
      id: null,
      serial: null,
      site: null,
      owner: null,
    }
  }

  fromJson(meterData) {
    this.meter = {
      id: meterData.id,
      serial: meterData.mpm_meter.serial_number,
      site: meterData.stm_customer.site.mpm_mini_grid.name,
      owner:
        meterData.stm_customer.mpm_person.name +
        " " +
        meterData.stm_customer.mpm_person.surname,
    }
    return this.meter
  }

  updateList(data) {
    this.list = []
    for (let m in data) {
      let meter = this.fromJson(data[m])
      this.list.push(meter)
    }
  }

  async syncMeters() {
    try {
      let response = await this.repository.sync()
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
      let response = await this.repository.syncCheck()
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

  async getMetersCount() {
    try {
      let response = await this.repository.count()
      if (response.status === 200) {
        this.count = response.data
        return this.count
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
