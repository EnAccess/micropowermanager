import { EventBus } from "@/shared/eventbus"
import { ErrorHandler } from "@/Helpers/ErrorHandler"
import AuthenticationRepository from "@/repositories/AuthenticationRepository"

export class AuthenticationService {
  constructor() {
    this.repository = AuthenticationRepository
    this.authenticateUser = {
      name: null,
      id: null,
      email: null,
      token: null,
      remaining_time: 0,
      intervalId: 0,
      companyId: null,
    }
  }

  _fetchData(data) {
    try {
      this.authenticateUser = {
        id: data.user.id,
        email: data.user.email,
        token: data.access_token,
        remaining_time: data.expires_in,
        name: data.user.name,
        intervalId: 0,
        companyId: data.user.company_id,
      }
      localStorage.setItem("token", this.authenticateUser.token)
      this.startTimer()
      return this.authenticateUser
    } catch (e) {
      return this.setAuthenticateUserEmpty(this.authenticateUser.intervalId)
    }
  }

  async authenticate(email, password) {
    try {
      let userPM = {
        email: email,
        password: password,
      }
      let response = await this.repository.login(userPM)
      if (response.status === 200) {
        return this._fetchData(response.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async refreshToken(token, intervalId) {
    try {
      let response = await this.repository.refresh(token)
      clearInterval(intervalId)
      if (response.status === 200) {
        return this._fetchData(response.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async logOut(intervalId) {
    this.stopTimer(intervalId)
    this.setAuthenticateUserEmpty(intervalId)
  }

  startTimer() {
    if (this.authenticateUser.remaining_time <= 0) return
    this.authenticateUser.intervalId = setInterval(() => {
      this.authenticateUser.remaining_time--
      if (
        this.authenticateUser.remaining_time <= 300 &&
        this.authenticateUser.remaining_time > 0
      ) {
        EventBus.$emit("ask.for.extend", this.authenticateUser.remaining_time)
      } else if (this.authenticateUser.remaining_time === 0) {
        EventBus.$emit("session.end", true)
        clearInterval(this.authenticateUser.intervalId)
      }
    }, 1000)
  }

  stopTimer(intervalId) {
    clearInterval(intervalId)
  }

  setAuthenticateUserEmpty(intervalId) {
    clearInterval(intervalId)
    this.authenticateUser = {}
    return this.authenticateUser
  }
}
