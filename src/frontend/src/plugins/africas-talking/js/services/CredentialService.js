import { ErrorHandler } from "@/Helpers/ErrorHandler"
import CredentialRepository from "../repositories/CredentialRepository"
import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = {
      id: null,
      apiKey: null,
      username: null,
      shortCode: null,
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
