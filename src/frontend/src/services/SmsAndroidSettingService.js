import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SmsAndroidSettingRepository from "@/repositories/SmsAndroidSettingRepository"

export class SmsAndroidSettingService {
  constructor() {
    this.repository = SmsAndroidSettingRepository
    this.list = []
    this.smsAndroidSetting = {
      id: null,
      url: null,
      token: null,
      key: null,
      callback: null,
    }
  }
  fromJson(smsAndroidSettings) {
    this.list = []
    for (let s in smsAndroidSettings) {
      let smsAndroidSetting = {
        id: smsAndroidSettings[s].id,
        url: smsAndroidSettings[s].url,
        token: smsAndroidSettings[s].token,
        key: smsAndroidSettings[s].key,
        callback: smsAndroidSettings[s].callback,
      }
      this.list.push(smsAndroidSetting)
    }
  }
  async getSmsAndroidSettings() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        this.fromJson(response.data.data)
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }
  async updateSmsAndroidSettings(smsAndroidSetting) {
    let smsAndroidSettingPm = {
      id: smsAndroidSetting.id,
      url: smsAndroidSetting.url,
      token: smsAndroidSetting.token,
      key: smsAndroidSetting.key,
      callback: smsAndroidSetting.callback,
    }
    return await this.repository.update(smsAndroidSettingPm)
  }
  async createSmsAndroidSettings(smsAndroidSetting) {
    let smsAndroidSettingPm = {
      token: smsAndroidSetting.token,
      key: smsAndroidSetting.key,
      callback: smsAndroidSetting.callback,
    }
    return await this.repository.create(smsAndroidSettingPm)
  }
  async removeSmsAndroidSetting(smsAndroidSettingId) {
    if (smsAndroidSettingId > 0) {
      try {
        let response = await this.repository.delete(smsAndroidSettingId)
        if (response.status === 200) {
          this.fromJson(response.data.data)
          return response.data.data
        } else {
          return new ErrorHandler(response.error, "http", response.status)
        }
      } catch (e) {
        let errorMessage = e.response.data.message
        return new ErrorHandler(errorMessage, "http")
      }
    } else {
      let smsAndroidSetting = this.list.filter(
        (x) => x.id === smsAndroidSettingId,
      )[0]
      if (smsAndroidSetting !== null) {
        for (let i = 0; i < this.list.length; i++) {
          if (this.list[i].id === smsAndroidSetting.id) {
            this.list.splice(i, 1)
          }
        }
      }
    }
  }
  async saveSmsAndroidSetting(smsAndroidSetting) {
    try {
      let response
      if (smsAndroidSetting.id < 0) {
        response = await this.createSmsAndroidSettings(smsAndroidSetting)
      } else {
        response = await this.updateSmsAndroidSettings(smsAndroidSetting)
      }
      if (response.status === 200 || response.status === 201) {
        this.fromJson(response.data.data)
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  addAdditionalSmsAndroidSettings() {
    let smsAndroidSetting = {
      id: -1 * Math.floor(Math.random() * 10000000),
      token: "",
      key: "",
      callback: "",
    }
    this.list.push(smsAndroidSetting)
  }
}
