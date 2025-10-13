import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { SyncSettingService } from "./SyncSettingService"
import SettingRepository from "../repositories/SettingRepository"

export class SettingService {
  constructor() {
    this.repository = SettingRepository
    this.syncSettingsService = new SyncSettingService()
    this.syncList = []
  }

  updateSyncList(data) {
    this.syncList = []
    for (let s in data) {
      this.syncList.push(data[s].data.attributes)
    }
  }

  // Initialize with default sync settings for Prospect
  initializeDefaultSyncSettings() {
    this.syncList = [
      {
        id: 1,
        actionName: "Installations",
        apiToken: "",
      },
    ]
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
      // For now, just simulate success since backend API doesn't exist yet
      // TODO: Uncomment when backend API is ready
      // await this.syncSettingsService.updateSyncSettings(this.syncList)
      
      // Simulate API call delay
      await new Promise(resolve => setTimeout(resolve, 500))
      
      return true
    } catch (e) {
      let errorMessage = e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
