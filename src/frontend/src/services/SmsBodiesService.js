import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SmsBodiesRepository from "@/repositories/SmsBodiesRepository"

/** References used for transaction confirmation SMS (token-focused, all channels). */
const TRANSACTION_CONFIRMATION_REFERENCES = [
  "TokenConfirmationMeter",
  "TokenConfirmationSHS",
  "TransactionConfirmationNoToken",
]

export class SmsBodiesService {
  constructor() {
    this.repository = SmsBodiesRepository
    this.reminderList = []
    this.confirmationList = []
    this.resendInformationList = []
    this.smsBody = {
      id: null,
      reference: null,
      body: "",
      placeholder: null,
      title: null,
      variables: [],
      validation: false,
    }
  }

  fromJson(smsBodies) {
    this.reminderList = []
    this.confirmationList = []
    this.resendInformationList = []

    for (let s in smsBodies) {
      const raw = smsBodies[s]
      const vars = (raw.variables || "")
        .split(",")
        .map((v) => v.trim())
        .filter(Boolean)
      let smsBody = {
        id: raw.id,
        reference: raw.reference,
        body: raw.body ?? "",
        title: raw.title,
        placeholder: raw.place_holder,
        variables: vars,
      }
      smsBody.validation = smsBody.body.length > 0

      if (smsBody.reference.includes("Reminder")) {
        this.reminderList.push(smsBody)
      } else if (smsBody.reference.includes("ResendInformation")) {
        this.resendInformationList.push(smsBody)
      } else if (
        TRANSACTION_CONFIRMATION_REFERENCES.includes(smsBody.reference)
      ) {
        this.confirmationList.push(smsBody)
      }
    }
  }

  async getSmsBodies() {
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
  getSmsBody(sms) {
    const smsBody = {
      id: sms.id,
      reference: sms.reference,
      body: sms.body,
    }
    return smsBody
  }
  async updateSmsBodies(tabName) {
    try {
      let smsBodiesPM = []
      if (tabName === "confirmation") {
        smsBodiesPM.push(this.confirmationList.map(this.getSmsBody))
      } else if (tabName === "reminder") {
        smsBodiesPM.push(this.reminderList.map(this.getSmsBody))
      } else {
        smsBodiesPM.push(this.resendInformationList.map(this.getSmsBody))
      }
      let response = await this.repository.update(smsBodiesPM)
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
