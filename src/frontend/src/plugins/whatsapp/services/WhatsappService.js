import WhatsappRepository from "../repositories/WhatsappRepository.js"

import {
  convertObjectKeysToCamelCase,
  convertObjectKeysToSnakeCase,
} from "@/Helpers/Utils.js"

export class WhatsappService {
  constructor() {
    this.repository = WhatsappRepository
    // accessToken must exist here so Vue tracks it; the API never returns it
    this.credential = {
      id: null,
      phoneNumberId: "",
      accessToken: "",
      appSecret: "",
      webhookVerifyToken: "",
      hasAppSecret: false,
      wabaId: "",
      graphVersion: "v23.0",
      templateLanguage: "en",
      receiptTemplate: "mpm_payment_receipt",
      reminderTemplate: "mpm_low_balance",
      fallbackToSms: true,
      hasToken: false,
    }
  }

  async getCredential() {
    const { data } = await this.repository.getCredential()
    this.credential = {
      ...convertObjectKeysToCamelCase(data.data),
      accessToken: "",
      appSecret: "",
    }
    return this.credential
  }

  async updateCredential() {
    const params = convertObjectKeysToSnakeCase(this.credential)
    delete params.has_token
    delete params.has_app_secret
    const { data } = await this.repository.updateCredential(params)
    this.credential = {
      ...convertObjectKeysToCamelCase(data.data),
      accessToken: "",
      appSecret: "",
    }
    return this.credential
  }

  async testConnection() {
    const { data } = await this.repository.testConnection()
    return data
  }

  async sendTest(phone, useHelloWorld) {
    const { data } = await this.repository.sendTest({
      phone,
      use_hello_world: useHelloWorld,
    })
    return data
  }

  async getMessages(page = 1) {
    const { data } = await this.repository.getMessages(page)
    return data
  }

  async getOptIns(page = 1) {
    const { data } = await this.repository.getOptIns(page)
    return data
  }

  async addOptIn(phone) {
    const { data } = await this.repository.addOptIn(phone)
    return data
  }

  async revokeOptIn(id) {
    const { data } = await this.repository.revokeOptIn(id)
    return data
  }
}
