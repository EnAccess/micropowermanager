import CredentialRepository from "../repositories/CredentialRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils.js"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = {
      id: null,
      userName: null,
      password: null,
    }
  }
  fromJson(credentialData) {
    this.credential = {
      id: credentialData.id,
      userName: credentialData.user_name,
      password: credentialData.password,
    }
    return this.credential
  }
  async getCredential() {
    try {
      const { data, status, error } = await this.repository.get()
      if (status !== 200) return new ErrorHandler(error, "http", status)
      return this.fromJson(data.data)
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
  async updateCredential() {
    try {
      const params = convertObjectKeysToSnakeCase(this.credential)
      const { data, status, error } = await this.repository.put(params)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return this.fromJson(data.data)
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
