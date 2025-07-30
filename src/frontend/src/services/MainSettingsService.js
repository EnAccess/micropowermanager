import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MainSettingsRepository from "@/repositories/MainSettingsRepository"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils"

export class MainSettingsService {
  constructor() {
    this.repository = MainSettingsRepository
    this.mainSettings = {
      id: null,
      siteTitle: null,
      companyName: null,
      currency: null,
      country: null,
      language: null,
      vatEnergy: null,
      vatAppliance: null,
      usageType: null,
      protectedPagePassword: null,
    }
  }

  async list() {
    try {
      const { data, status, error } = await this.repository.list()
      if (status !== 200) throw new ErrorHandler(error, "http", status)
      this.mainSettings = convertObjectKeysToCamelCase(data.data)
      this.mainSettings.protectedPagePassword = null
      return this.mainSettings
    } catch (e) {
      const errorMessage = e.response.data.message
      throw new ErrorHandler(errorMessage, "http")
    }
  }

  async update() {
    try {
      const postData = convertObjectKeysToSnakeCase(this.mainSettings)
      const { data, status, error } = await this.repository.update(
        this.mainSettings.id,
        postData,
      )
      if (status !== 200 && status !== 201)
        throw new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      throw new ErrorHandler(errorMessage, "http")
    }
  }
}
