import { ErrorHandler } from "@/Helpers/ErrorHandler"
import SmsVariableDefaultValueRepository from "../repositories/SmsVariableDefaultValueRepository"

export class SmsVariableDefaultValueService {
  constructor() {
    this.repository = SmsVariableDefaultValueRepository
    this.list = []
    this.shownMessage = "Your message will seem like.."
  }

  fromJson(smsVariableDefaultValues) {
    this.list = []
    for (let s in smsVariableDefaultValues) {
      let defaultValue = smsVariableDefaultValues[s]
      let smsVariableDefaultValue = {
        variable: "[" + defaultValue.variable + "]",
        value: defaultValue.value,
      }
      this.list.push(smsVariableDefaultValue)
    }
  }

  async getSmsVariableDefaultValues() {
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

  prepareShownMessage(body, list) {
    this.shownMessage = ""
    list.forEach((e) => {
      body = body.replaceAll(e.variable, e.value)
    })
    this.shownMessage = body
    if (!body.length) {
      this.shownMessage = "Your message will seem like.."
    }
    return this.shownMessage
  }
}
