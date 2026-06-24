import CredentialRepository from "../repositories/CredentialRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = {
      apiKey: null,
      publicKey: null,
      serviceProviderCode: null,
      live: false,
    }
  }
  fromJson(credentialData) {
    this.credential = {
      apiKey: credentialData.api_key,
      publicKey: credentialData.public_key,
      serviceProviderCode: credentialData.service_provider_code,
      live: credentialData.live,
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
        api_key: this.credential.apiKey,
        public_key: this.credential.publicKey,
        service_provider_code: this.credential.serviceProviderCode,
        live: this.credential.live,
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
}
