import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import RegistrationTailRepository from "@/repositories/RegistrationTailRepository.js"

export class RegistrationTailService {
  constructor() {
    this.repository = RegistrationTailRepository
    this.registrationTail = []
  }

  async getRegistrationTail() {
    try {
      let response = await this.repository.list()
      if (response.status === 200) {
        this.registrationTail = response.data.data
        return this.registrationTail
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async adjustStep(stepId) {
    try {
      let response = await this.repository.update(stepId, { adjusted: true })

      if (response.status === 200) {
        this.registrationTail = response.data.data
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
