import { ErrorHandler } from "@/Helpers/ErrorHandler"
import MailSettingsRepository from "@/repositories/MailSettingsRepository"

export class MailSettingsService {
  constructor() {
    this.repository = MailSettingsRepository
    this.mailSettings = {
      mailHost: null,
      mailPort: null,
      mailEncryption: null,
      mailUserName: null,
      mailPassword: null,
    }
  }

  fromJson(mailSettings) {
    this.mailSettings = {
      id: mailSettings.id,
      mailHost: mailSettings.mail_host,
      mailPort: mailSettings.mail_port,
      mailEncryption: mailSettings.mail_encryption,
      mailUserName: mailSettings.mail_username,
      mailPassword: mailSettings.mail_password,
    }
    return this.mailSettings
  }

  async list() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        return this.fromJson(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  async update() {
    try {
      const mailSettingsPm = {
        id: this.mailSettings.id,
        mail_host: this.mailSettings.mailHost,
        mail_port: parseInt(this.mailSettings.mailPort),
        mail_encryption: this.mailSettings.mailEncryption,
        mail_username: this.mailSettings.mailUserName,
        mail_password: this.mailSettings.mailPassword,
      }
      if (mailSettingsPm.id === undefined) {
        return await this.create(mailSettingsPm)
      } else {
        let response = await this.repository.update(
          mailSettingsPm.id,
          mailSettingsPm,
        )
        if (response.status === 200) {
          return this.fromJson(response.data.data)
        } else {
          return new ErrorHandler(response.error, "http", response.status)
        }
      }
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }

  async create(mailSettingsPm) {
    try {
      let response = await this.repository.create(mailSettingsPm)
      if (response.status === 201) {
        return this.fromJson(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      return new ErrorHandler(e.response.data.message, "http")
    }
  }
}
