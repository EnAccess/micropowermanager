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

    if (settingData.setting_type === "spark_sync_setting") {
      setting.settingType = {
        id: settingData.setting.id,
        actionName: settingData.setting.action_name,
        syncInValueStr: settingData.setting.sync_in_value_str,
        syncInValueNum: settingData.setting.sync_in_value_num,
        maxAttempts: settingData.setting.max_attempts,
      }
    } else {
      setting.settingType = {
        id: settingData.setting.id,
        enabled: settingData.setting.enabled > 0,
        state: settingData.setting.state,
        NotSendElderThanMins: settingData.setting.not_send_elder_than_mins,
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
        this.list.filter((x) => x.settingTypeName === "spark_sync_setting"),
      )
    } catch (e) {
      let errorMessage = e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateSmsSettings() {
    try {
      await this.smsSettingsService.updateSmsSettings(
        this.list.filter((x) => x.settingTypeName === "spark_sms_setting"),
      )
    } catch (e) {
      let errorMessage = e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
