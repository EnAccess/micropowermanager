import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils.js"
import DeviceRepository from "@/repositories/DeviceRepository.js"

export class DeviceService {
  constructor() {
    this.list = []
    this.device = {}
    this.repository = DeviceRepository
  }

  async update(Id, device) {
    try {
      const params = convertObjectKeysToSnakeCase(device)
      const { data, status, error } = await this.repository.update(Id, params)
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.device = convertObjectKeysToCamelCase(data.data)

      return this.device
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getDevices(params = {}) {
    try {
      const { data, status, error } = await this.repository.list(params)
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.list = data.data.map((device) =>
        convertObjectKeysToCamelCase(device),
      )

      return this.list
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getAvailableDevicesForAppliance(applianceId) {
    const params = {
      unassigned: 1,
      appliance_id: applianceId,
      per_page: 50,
    }
    return this.getDevices(params)
  }
}
