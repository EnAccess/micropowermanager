import { ErrorHandler } from "@/Helpers/ErrorHandler"
import CredentialRepository from "../repositories/CredentialRepository"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = {
      authUrl: null,
      apiUrl: null,
      clientId: null,
      clientSecret: null,
    }
  }
  fromJson(credentialData) {
    this.credential = {
      authUrl: credentialData.auth_url,
      apiUrl: credentialData.api_url,
      clientId: credentialData.client_id,
      clientSecret: credentialData.client_secret,
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
      let credentialPayload = {
        auth_url: this.credential.authUrl,
        api_url: this.credential.apiUrl,
        client_id: this.credential.clientId,
        client_secret: this.credential.clientSecret,
      }
      let response = await this.repository.put(credentialPayload)
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
  async checkCredential() {
    try {
      let credentialPayload = {
        auth_url: this.credential.authUrl,
        api_url: this.credential.apiUrl,
        client_id: this.credential.clientId,
        client_secret: this.credential.clientSecret,
      }
      let response = await this.repository.check(credentialPayload)

      if (![200, 201].includes(response.status)) {
        return new ErrorHandler(response.error, "http", response.status)
      }

      return response.data.valid
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http", e.response.status)
    }
  }
}
