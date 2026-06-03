import SettingRepository from "../repositories/SettingRepository.js"

import { SmsSettingService } from "./SmsSettingService.js"
import { SyncSettingService } from "./SyncSettingService.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"

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
    let settingType = settingData.setting ?? {}

    if (settingData.setting_type === "steama_sync_setting") {
      setting.settingType = {
        id: settingType.id,
        actionName: settingType.action_name,
        syncInValueStr: settingType.sync_in_value_str,
        syncInValueNum: settingType.sync_in_value_num,
        maxAttempts: settingType.max_attempts,
      }
    } else {
      setting.settingType = {
        id: settingType.id,
        enabled: settingType.enabled > 0,
        state: settingType.state,
        NotSendElderThanMins: settingType.not_send_elder_than_mins,
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
      let errorMessage = e.response?.data?.message ?? e.message
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
