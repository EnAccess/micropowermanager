import CredentialRepository from "../repositories/CredentialRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils.js"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = {
      id: null,
      apiKey: null,
      deviceId: null,
    }
  }

  async getCredential() {
    try {
      const { data, status, error } = await this.repository.get()
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.credential = convertObjectKeysToCamelCase(data.data)

      return this.credential
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateCredential() {
    try {
      const params = convertObjectKeysToSnakeCase(this.credential)
      const { data, status, error } = await this.repository.update(params)
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.credential = convertObjectKeysToCamelCase(data.data)

      return this.credential
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
