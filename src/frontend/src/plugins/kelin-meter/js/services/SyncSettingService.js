import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SyncSettingRepository from "../repositories/SyncSettingRepository"

export class SyncSettingService {
  constructor() {
    this.repository = SyncSettingRepository
    this.list = []
    this.syncSetting = {
      id: null,
      actionName: null,
      syncInMins: null,
      timeValueInt: null,
      timeValueStr: null,
      maxAttempts: null,
    }
  }

  async updateSyncSettings(syncSettings) {
    try {
      let syncListPM = []
      for (let s in syncSettings) {
        let settingPm = {
          id: syncSettings[s].id,
          action_name: syncSettings[s].actionName,
          sync_in_value_str: syncSettings[s].syncInValueStr,
          sync_in_value_num: syncSettings[s].syncInValueNum,
          max_attempts: syncSettings[s].maxAttempts,
        }
        syncListPM.push(settingPm)
      }
      let response = await this.repository.update(syncListPM)
      if (response.status === 200) {
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
