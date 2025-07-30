import { ErrorHandler } from "@/Helpers/ErrorHandler"
import UserPasswordRepository from "@/repositories/UserPasswordRepository"

export class UserPasswordService {
  constructor() {
    this.repository = UserPasswordRepository
    this.user = {
      id: null,
      password: "",
      confirmPassword: "",
    }
  }

  async update(id) {
    this.user.id = id
    const userDataPm = {
      id: this.user.id,
      password: this.user.password,
      confirm_password: this.user.confirmPassword,
    }
    try {
      const { status, data } = await this.repository.put(userDataPm)
      if (!status === 200) {
        return new ErrorHandler("Failed", "http", status)
      }
      this.resetUserPassword()
      return data.data
    } catch (e) {
      this.resetUserPassword()
      let errorMessage = e.response.data.message
      return new ErrorHandler(
        errorMessage,
        "http",
        e.response.data.data.status_code,
      )
    }
  }

  async forgotPassword(email) {
    try {
      const { status, data, error } = await this.repository.post(email)
      if (!status === 200) {
        return new ErrorHandler(error, "http", status)
      }
      return data.data
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(
        errorMessage,
        "http",
        e.response.data.data.status_code,
      )
    }
  }
  resetUserPassword() {
    this.user = {
      password: null,
      confirmPassword: null,
    }
  }
}
