import SettingsRepository from "../repositories/SettingsRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"

export class SettingsService {
  constructor() {
    this.repository = SettingsRepository
    this.financingPrograms = [
      "REA_NEP_PBG",
      "REA_NEP_OBF",
      "BRILHO_SHS",
      "UEF_SSPU",
    ]
    this.financingId = ""
  }

  async getSettings() {
    try {
      const { data, status, error } = await this.repository.get()
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.financingId = data.data.financing_id ?? ""

      return this.financingId
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateSettings() {
    try {
      const { data, status, error } = await this.repository.update({
        financing_id: this.financingId || null,
      })
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.financingId = data.data.financing_id ?? ""

      return this.financingId
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
