import { ErrorHandler } from "@/Helpers/ErrorHandler"
import CredentialRepository from "../repositories/CredentialRepository"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = {
      id: null,
      apiKey: null,
      apiSecret: null,
      isAuthenticated: null,
    }
  }

  fromJson(credentialData) {
    this.credential = {
      id: credentialData.id,
      apiKey: credentialData.api_key,
      apiSecret: credentialData.api_secret,
      isAuthenticated: credentialData.is_authenticated,
      alert: credentialData.alert,
    }
    return this.credential
  }

  async getCredential() {
    try {
      let response = await this.repository.get()
      if (response.status === 200) {
        return this.fromJson(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateCredential() {
    try {
      let credentialPM = {
        id: this.credential.id,
        api_key: this.credential.apiKey,
        api_secret: this.credential.apiSecret,
      }
      let response = await this.repository.put(credentialPM)
      if (response.status === 200 || response.status === 201) {
        return this.fromJson(response.data.data)
      } else {
        return new ErrorHandler(response.error, "http", response.status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
