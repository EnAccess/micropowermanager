import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { SyncSettingService } from "./SyncSettingService"
import SettingRepository from "../repositories/SettingRepository"

export class SettingService {
  constructor() {
    this.repository = SettingRepository
    this.syncSettingsService = new SyncSettingService()
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
      this.initializeDefaultSyncSettings()
      return true
    } catch (e) {
      let errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateSyncSettings() {
    try {
      const response = await this.syncSettingsService.updateSyncSettings(
        this.list,
      )
      return response
    } catch (e) {
      let errorMessage = e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
