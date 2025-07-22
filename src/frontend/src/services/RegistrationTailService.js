import { ErrorHandler } from "@/Helpers/ErrorHandler"
import RegistrationTailRepository from "@/repositories/RegistrationTailRepository"

export class RegistrationTailService {
  constructor() {
    this.repository = RegistrationTailRepository
    this.registrationTail = {}
  }

  async getRegistrationTail() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        this.registrationTail = response.data.data[0]
        return this.registrationTail
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateRegistrationTail(tailId, tag, tail) {
    try {
      for (const tailObj of tail) {
        for (const tailObjKey in tailObj) {
          if (tailObjKey === "tag" && tailObj[tailObjKey] === tag) {
            tailObj["adjusted"] = true
          }
        }
      }

      let response = await this.repository.update(tailId, { tail: tail })

      if (response.status === 200) {
        this.registrationTail = response.data.data[0]

        return this.registrationTail
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
