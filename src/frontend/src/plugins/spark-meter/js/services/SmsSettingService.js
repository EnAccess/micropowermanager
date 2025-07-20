import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SmsSettingRepository from "../repositories/SmsSettingRepository"

export class SmsSettingService {
  constructor() {
    this.repository = SmsSettingRepository
    this.list = []
    this.smsSetting = {
      id: null,
      enabled: null,
      state: null,
      NotSendElderThanMins: null,
    }
  }

  async updateSmsSettings(smsSettings) {
    try {
      let smsListPM = []
      for (let s in smsSettings) {
        let settingPm = {
          id: smsSettings[s].settingType.id,
          enabled: smsSettings[s].settingType.enabled,
          state: smsSettings[s].settingType.state,
          not_send_elder_than_mins:
            smsSettings[s].settingType.NotSendElderThanMins,
        }
        smsListPM.push(settingPm)
      }
      let response = await this.repository.update(smsListPM)
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
