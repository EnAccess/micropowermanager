import CredentialRepository from "../repositories/CredentialRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = {
      id: null,
      apiUrl: null,
      userId: null,
      apiKey: null,
    }
  }
  fromJson(credentialData) {
    this.credential = {
      id: credentialData.id,
      apiUrl: credentialData.api_url,
      userId: credentialData.user_id,
      apiKey: credentialData.api_key,
    }
    return this.credential
  }
  toJson() {
    return {
      api_url: this.credential.apiUrl,
      user_id: this.credential.userId,
      api_key: this.credential.apiKey,
    }
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
      let response = await this.repository.put(this.toJson())
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
      let response = await this.repository.check(this.toJson())
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
