import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SmsApplianceRemindRateRepository from "@/repositories/SmsApplianceRemindRateRepository"

export class SmsApplianceRemindRateService {
  constructor() {
    this.repository = SmsApplianceRemindRateRepository
    this.list = []
    this.smsApplianceRemindRate = {
      id: null,
      applianceTypeId: null,
      applianceType: null,
      overdueRemindRate: null,
      remindRate: null,
    }
  }
  fromJson(applianceTypes) {
    if (!applianceTypes.length) {
      return
    }
    this.list = applianceTypes.map((applianceType) => {
      this.smsApplianceRemindRate = {
        id:
          applianceType.sms_reminder_rate == null ||
          applianceTypes.sms_reminder_rate === undefined
            ? -1 * Math.floor(Math.random() * 10000000)
            : applianceType.sms_reminder_rate.id,
        applianceTypeId: applianceType.id,
        applianceType: applianceType.name,
        overdueRemindRate:
          applianceType.sms_reminder_rate == null ||
          applianceType.sms_reminder_rate === undefined
            ? 0
            : applianceType.sms_reminder_rate.overdue_remind_rate,
        remindRate:
          applianceType.sms_reminder_rate == null ||
          applianceType.sms_reminder_rate === undefined
            ? 0
            : applianceType.sms_reminder_rate.remind_rate,
      }
      return this.smsApplianceRemindRate
    })
  }
  async getSmsApplianceRemindRates() {
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
  async updateSmsApplianceRemindRate() {
    try {
      let smsApplianceRemindRatePm = {
        id: this.smsApplianceRemindRate.id,
        appliance_type_id: this.smsApplianceRemindRate.applianceTypeId,
        overdue_remind_rate: this.smsApplianceRemindRate.overdueRemindRate,
        remind_rate: this.smsApplianceRemindRate.remindRate,
      }
      let response
      if (smsApplianceRemindRatePm.id < 0) {
        response = await this.repository.create(smsApplianceRemindRatePm)
      } else {
        response = await this.repository.update(smsApplianceRemindRatePm)
      }
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
