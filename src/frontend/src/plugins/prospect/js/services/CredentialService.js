import { ErrorHandler } from "@/Helpers/ErrorHandler"
import CredentialRepository from "../repositories/CredentialRepository"
import { convertObjectKeysToCamelCase } from "@/Helpers/Utils"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.list = []
  }

  updateCredentialList(data) {
    this.list = []
    if (Array.isArray(data)) {
      for (let c in data) {
        const item = data[c]
        let attrs, id
        
        if (item.data) {
          attrs = item.data.attributes || item.data
          id = item.data.id || attrs?.id
        } else {
          attrs = item
          id = item.id
        }
        
        const credential = convertObjectKeysToCamelCase(attrs)
        credential.id = id || credential.id
        this.list.push(credential)
      }
    } else {
      const credential = convertObjectKeysToCamelCase(data)
      this.list = [
        {
          id: credential.id,
          apiUrl: credential.apiUrl,
          apiToken: credential.apiToken,
        },
        {
          id: credential.id,
          apiUrl: credential.apiUrl?.replace('/installations', '/payments_ts') || '',
          apiToken: credential.paymentsApiToken || '',
        },
      ]
    }
  }

  async getCredential() {
    try {
      const { data, status, error } = await this.repository.get()
      if (status !== 200) return new ErrorHandler(error, "http", status)
      
      if (data.data) {
        if (Array.isArray(data.data)) {
          this.updateCredentialList(data.data)
        } else {
          this.updateCredentialList(data.data)
        }
      }

      return this.list
    } catch (e) {
      const errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async updateCredential() {
    try {
      let payload = []
      for (let c in this.list) {
        payload.push({
          id: this.list[c].id,
          api_url: this.list[c].apiUrl,
          api_token: this.list[c].apiToken,
        })
      }
      
      const { data, status, error } = await this.repository.update(payload)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)
      
      if (data.data) {
        if (Array.isArray(data.data)) {
          this.updateCredentialList(data.data)
        } else {
          this.updateCredentialList(data.data)
        }
      }

      return this.list
    } catch (e) {
      const errorMessage = e.response?.data?.message || e.message
      return new ErrorHandler(errorMessage, "http")
    }
  }
}
