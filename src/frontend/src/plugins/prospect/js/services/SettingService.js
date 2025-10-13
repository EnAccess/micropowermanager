import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { SyncSettingService } from "./SyncSettingService"
import SettingRepository from "../repositories/SettingRepository"

export class SettingService {
  constructor() {
    this.repository = SettingRepository
    this.syncSettingsService = new SyncSettingService()
    // Keep `list` like Kelin for UI binding; maintain `syncList` for backward compatibility
    this.list = []
    this.syncList = []
  }

  updateSyncList(data) {
    this.list = []
    this.syncList = []
    for (let s in data) {
      const attrs = data[s].data.attributes
      this.list.push(attrs)
      this.syncList.push(attrs)
    }
  }

  // Initialize with default sync settings for Prospect
  initializeDefaultSyncSettings() {
    this.list = [
      {
        id: 1,
        actionName: "Installations",
        syncInValueStr: "weekly",
        syncInValueNum: 1,
        maxAttempts: 3,
      },
    ]
    this.syncList = this.list
  }

  async getSettings() {
    try {
      // For now, initialize with default settings since API doesn't exist yet
      this.initializeDefaultSyncSettings()
      
      // TODO: Uncomment when backend API is ready
      // let syncResponse = await this.repository.getSyncSettings()
      // if (syncResponse.status === 200) {
      //   this.updateSyncList(syncResponse.data.data)
      // }

      return true
    } catch (e) {
      let errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateSyncSettings() {
    try {
      // Forward to SyncSettingService to perform real API call
      const response = await this.syncSettingsService.updateSyncSettings(this.list)
      return response
    } catch (e) {
      let errorMessage = e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
