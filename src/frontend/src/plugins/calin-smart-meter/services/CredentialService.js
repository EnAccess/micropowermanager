import { ErrorHandler } from "@/Helpers/ErrorHandler"
import CredentialRepository from "../repositories/CredentialRepository"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = {
      id: null,
      companyName: null,
      userName: null,
      password: null,
      passwordVend: null,
    }
  }
  fromJson(credentialData) {
    this.credential = {
      id: credentialData.id,
      companyName: credentialData.company_name,
      userName: credentialData.user_name,
      password: credentialData.password,
      passwordVend: credentialData.password_vend,
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
        company_name: this.credential.companyName,
        user_name: this.credential.userName,
        password: this.credential.password,
        password_vend: this.credential.passwordVend,
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
