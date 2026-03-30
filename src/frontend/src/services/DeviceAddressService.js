import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils.js"
import DeviceAddressRepository from "@/repositories/DeviceAddressRepository.js"

export class DeviceAddressService {
  constructor() {
    this.repository = DeviceAddressRepository
  }
  async updateDeviceAddresses(devices) {
    try {
      const params = devices.map((device) =>
        convertObjectKeysToSnakeCase(device),
      )
      const { data, status, error } = await this.repository.update(params)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
