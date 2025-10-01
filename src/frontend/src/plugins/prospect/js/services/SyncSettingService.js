import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SyncSettingRepository from "../repositories/SyncSettingRepository"

export class SyncSettingService {
  constructor() {
    this.repository = SyncSettingRepository
  }

  async updateSyncSettings(syncList) {
    try {
      let response = await this.repository.updateSyncSettings(syncList)
      if (response.status !== 200) {
        return new ErrorHandler(response.error, "http", response.status)
      }
      return response
    } catch (e) {
      let errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
