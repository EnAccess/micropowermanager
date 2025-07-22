import { ErrorHandler } from "@/Helpers/ErrorHandler"
import { SyncSettingService } from "./SyncSettingService"
import { SmsSettingService } from "./SmsSettingService"
import SettingRepository from "../repositories/SettingRepository"

export class SettingService {
  constructor() {
    this.repository = SettingRepository
    this.syncSettingsService = new SyncSettingService()
    this.smsSettingsService = new SmsSettingService()
    this.list = []
    this.setting = {
      id: null,
      settingTypeName: null,
      settingTypeId: null,
      settingType: {},
    }
  }

  fromJson(settingData) {
    let setting = {
      id: settingData.id,
      settingTypeName: settingData.setting_type,
      settingTypeId: settingData.setting_id,
      settingType: {},
    }

    if (settingData.setting_type === "steama_sync_setting") {
      setting.settingType = {
        id: settingData.setting_sync.id,
        actionName: settingData.setting_sync.action_name,
        syncInValueStr: settingData.setting_sync.sync_in_value_str,
        syncInValueNum: settingData.setting_sync.sync_in_value_num,
        maxAttempts: settingData.setting_sync.max_attempts,
      }
    } else {
      setting.settingType = {
        id: settingData.setting_sms.id,
        enabled: settingData.setting_sms.enabled > 0,
        state: settingData.setting_sms.state,
        NotSendElderThanMins: settingData.setting_sms.not_send_elder_than_mins,
      }
    }
    return setting
  }

  updateList(data) {
    this.list = []
    for (let s in data) {
      let setting = this.fromJson(data[s])
      this.list.push(setting)
    }
  }

  async getSettings() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        return this.updateList(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateSyncSettings() {
    try {
      await this.syncSettingsService.updateSyncSettings(
        this.list.filter((x) => x.settingTypeName === "steama_sync_setting"),
      )
    } catch (e) {
      let errorMessage = e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateSmsSettings() {
    try {
      await this.smsSettingsService.updateSmsSettings(
        this.list.filter((x) => x.settingTypeName === "steama_sms_setting"),
      )
    } catch (e) {
      let errorMessage = e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
