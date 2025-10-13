import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SyncSettingRepository from "../repositories/SyncSettingRepository"

export class SyncSettingService {
  constructor() {
    this.repository = SyncSettingRepository
  }

  async updateSyncSettings(syncList) {
    try {
      // Map to backend expected payload schema
      let payload = []
      for (let s in syncList) {
        payload.push({
          id: syncList[s].id,
          action_name: syncList[s].actionName,
          sync_in_value_str: syncList[s].syncInValueStr,
          sync_in_value_num: syncList[s].syncInValueNum,
          max_attempts: syncList[s].maxAttempts,
        })
      }
      let response = await this.repository.updateSyncSettings(payload)
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
