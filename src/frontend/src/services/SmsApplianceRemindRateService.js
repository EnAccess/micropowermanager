import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import SmsApplianceRemindRateRepository from "@/repositories/SmsApplianceRemindRateRepository.js"

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
      upcomingReminderEnabled: false,
      overdueReminderEnabled: false,
      createTicket: false,
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
          applianceType.sms_reminder_rate === undefined
            ? -1 * Math.floor(Math.random() * 10000000)
            : applianceType.sms_reminder_rate.id,
        applianceTypeId: applianceType.id,
        applianceType: applianceType.name,
        overdueRemindRate:
          applianceType.sms_reminder_rate == null ||
          applianceType.sms_reminder_rate === undefined
            ? 1
            : applianceType.sms_reminder_rate.overdue_remind_rate,
        remindRate:
          applianceType.sms_reminder_rate == null ||
          applianceType.sms_reminder_rate === undefined
            ? 3
            : applianceType.sms_reminder_rate.remind_rate,
        upcomingReminderEnabled:
          applianceType.sms_reminder_rate == null ||
          applianceType.sms_reminder_rate === undefined
            ? false
            : !!applianceType.sms_reminder_rate.upcoming_reminder_enabled,
        overdueReminderEnabled:
          applianceType.sms_reminder_rate == null ||
          applianceType.sms_reminder_rate === undefined
            ? false
            : !!applianceType.sms_reminder_rate.overdue_reminder_enabled,
        createTicket:
          applianceType.sms_reminder_rate == null ||
          applianceType.sms_reminder_rate === undefined
            ? false
            : !!applianceType.sms_reminder_rate.create_ticket,
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
        upcoming_reminder_enabled:
          this.smsApplianceRemindRate.upcomingReminderEnabled,
        overdue_reminder_enabled:
          this.smsApplianceRemindRate.overdueReminderEnabled,
        create_ticket: this.smsApplianceRemindRate.createTicket,
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
  async deleteSmsApplianceRemindRate(id) {
    try {
      let response = await this.repository.delete(id)
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
