import SyncSettingRepository from "../repositories/SyncSettingRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"

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

  /**
   * Entities whose last sync attempt failed (attempts > 0 resets to 0 on success, so this is a
   * reliable "needs resync" signal) rather than the full settings list.
   */
  async getNeedsResync() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        return response.data.data
          .filter((setting) => (setting.sync_action?.attempts ?? 0) > 0)
          .map((setting) => ({
            actionName: setting.action_name,
            attempts: setting.sync_action.attempts,
            maxAttempts: setting.max_attempts,
          }))
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response?.data?.message ?? e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateSyncSettings(syncSettings) {
    try {
      let syncListPM = []
      for (let s in syncSettings) {
        let settingPm = {
          id: syncSettings[s].settingType.id,
          action_name: syncSettings[s].settingType.actionName,
          sync_in_value_str: syncSettings[s].settingType.syncInValueStr,
          sync_in_value_num: syncSettings[s].settingType.syncInValueNum,
          max_attempts: syncSettings[s].settingType.maxAttempts,
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
