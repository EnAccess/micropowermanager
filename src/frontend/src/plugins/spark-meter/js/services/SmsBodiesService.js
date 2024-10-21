import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SmsBodiesRepository from "../repositories/SmsBodiesRepository"

export class SmsBodiesService {
  constructor() {
    this.repository = SmsBodiesRepository
    this.meterResetFeedbackList = []
    this.lowBalanceNotifierList = []
    this.balanceFeedbacksList = []
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
    this.meterResetFeedbackList = []
    this.lowBalanceNotifierList = []
    this.balanceFeedbacksList = []

    for (let s in smsBodies) {
      let smsBody = {
        id: smsBodies[s].id,
        reference: smsBodies[s].reference,
        body: smsBodies[s].body ?? "",
        title: smsBodies[s].title,
        placeholder: smsBodies[s].place_holder,
        variables: smsBodies[s].variables.split(","),
      }
      smsBody.validation = smsBody.body.length > 0

      if (smsBody.reference.includes("LowBalance")) {
        this.lowBalanceNotifierList.push(smsBody)
      } else if (smsBody.reference.includes("BalanceFeedback")) {
        this.balanceFeedbacksList.push(smsBody)
      } else {
        this.meterResetFeedbackList.push(smsBody)
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

  async updateSmsBodies(tabName) {
    try {
      let smsBodiesPM = []
      if (tabName === "notification-settings") {
        this.lowBalanceNotifierList.forEach((e) => {
          let smsBody = {
            id: e.id,
            reference: e.reference,
            body: e.body,
          }
          smsBodiesPM.push(smsBody)
        })
      } else if (tabName === "meter-reset-settings") {
        this.meterResetFeedbackList.forEach((e) => {
          let smsBody = {
            id: e.id,
            reference: e.reference,
            body: e.body,
          }
          smsBodiesPM.push(smsBody)
        })
      } else {
        this.balanceFeedbacksList.forEach((e) => {
          let smsBody = {
            id: e.id,
            reference: e.reference,
            body: e.body,
          }
          smsBodiesPM.push(smsBody)
        })
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
