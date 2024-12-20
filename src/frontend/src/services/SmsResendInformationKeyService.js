import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SmsResendInformationKeyRepository from "@/repositories/SmsResendInformationKeyRepository"

export class SmsResendInformationKeyService {
  constructor() {
    this.repository = SmsResendInformationKeyRepository
    this.smsResendInformationKey = {
      id: null,
      key: null,
    }
  }
  fromJson(smsResendInformationKey) {
    this.smsResendInformationKey = {
      id: smsResendInformationKey.id,
      key: smsResendInformationKey.key,
    }
  }
  async getResendInformationKeys() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        this.fromJson(response.data.data[0])
        return response.data.data
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let erorMessage = e.response.data.message
      return new ErrorHandler(erorMessage, "http")
    }
  }
  async updateResendInformationKey() {
    try {
      let smsResendInformationKeyPm = {
        id: this.smsResendInformationKey.id,
        key: this.smsResendInformationKey.key,
      }
      let response = await this.repository.update(smsResendInformationKeyPm)
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
  }
}
