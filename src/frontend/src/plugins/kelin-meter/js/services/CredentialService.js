import { ErrorHandler } from "@/Helpers/ErrorHandler"
import CredentialRepository from "../repositories/CredentialRepository"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = {
      id: null,
      username: null,
      password: null,
    }
  }
  async getCredential() {
    try {
      const { data, status, error } = await this.repository.get()
      if (status === 200) {
        this.credential = data.data.attributes
        return this.credential
      } else {
        return new ErrorHandler(error, "http", status)
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
        username: this.credential.username,
        password: this.credential.password,
        company_name: this.credential.companyName,
      }
      const { data, status, error } = await this.repository.put(credentialPM)
      if (status === 200 || status === 201) {
        this.credential = data.data.attributes
        return this.credential
      } else {
        return new ErrorHandler(error, "http", status)
      }
    } catch (e) {
      let errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
