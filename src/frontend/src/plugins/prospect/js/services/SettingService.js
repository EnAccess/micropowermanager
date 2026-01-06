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
      if (attrs.isEnabled === undefined) {
        attrs.isEnabled = true
      } else {
        attrs.isEnabled = Boolean(attrs.isEnabled)
      }
      this.list.push(attrs)
      this.syncList.push(attrs)
    }
  }

  initializeDefaultSyncSettings() {
    this.list = [
      {
        id: 1,
        actionName: "Installations",
        isEnabled: true,
        syncInValueStr: "weekly",
        syncInValueNum: 1,
        maxAttempts: 3,
      },
      {
        id: 2,
        actionName: "Payments",
        isEnabled: true,
        syncInValueStr: "weekly",
        syncInValueNum: 1,
        maxAttempts: 3,
      },
      {
        id: 3,
        actionName: "Customers",
        isEnabled: true,
        syncInValueStr: "weekly",
        syncInValueNum: 1,
        maxAttempts: 3,
      },
      {
        id: 4,
        actionName: "Agents",
        isEnabled: true,
        syncInValueStr: "weekly",
        syncInValueNum: 1,
        maxAttempts: 3,
      },
    ]
    this.syncList = this.list
  }

  async getSettings() {
    try {
      const response =
        await this.syncSettingsService.repository.getSyncSettings()
      if (
        response.status === 200 &&
        response.data?.data &&
        response.data.data.length > 0
      ) {
        const settings = response.data.data
        if (settings[0]?.data?.attributes) {
          this.updateSyncList(settings)
        } else {
          this.list = settings.map((item) => ({
            id: item.id,
            actionName: item.action_name,
            isEnabled: item.is_enabled !== undefined ? item.is_enabled : true,
            syncInValueStr: item.sync_in_value_str,
            syncInValueNum: item.sync_in_value_num,
            maxAttempts: item.max_attempts,
          }))
          this.syncList = this.list
        }
      } else {
        this.initializeDefaultSyncSettings()
      }
      return true
    } catch (e) {
      this.initializeDefaultSyncSettings()
      return true
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
