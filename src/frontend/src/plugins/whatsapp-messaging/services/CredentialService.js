import CredentialRepository from "../repositories/CredentialRepository.js"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"

export class CredentialService {
  constructor() {
    this.repository = CredentialRepository
    this.credential = this.fromJson({})
    this.templateErrors = []
  }

  // Secrets are write-only: the backend only reports whether they are set and their last four characters.
  fromJson(credentialData) {
    this.credential = {
      phoneNumberId: credentialData.phone_number_id ?? null,
      businessAccountId: credentialData.business_account_id ?? null,
      graphApiVersion: credentialData.graph_api_version ?? null,
      accessToken: null,
      appSecret: null,
      isAccessTokenSet: credentialData.is_access_token_set ?? false,
      accessTokenLastFour: credentialData.access_token_last_four ?? null,
      isAppSecretSet: credentialData.is_app_secret_set ?? false,
      appSecretLastFour: credentialData.app_secret_last_four ?? null,
      webhookUrl: credentialData.webhook_url ?? null,
      webhookVerifyToken: credentialData.webhook_verify_token ?? null,
      optInLink: credentialData.opt_in_link ?? null,
    }
    return this.credential
  }

  async getCredential() {
    try {
      const response = await this.repository.get()
      return this.fromJson(response.data.data)
    } catch (e) {
      return new ErrorHandler(e.response?.data?.message ?? e.message, "http")
    }
  }

  // A blank secret is sent as null so the backend keeps the stored one.
  async updateCredential() {
    try {
      const response = await this.repository.put({
        phone_number_id: this.credential.phoneNumberId,
        business_account_id: this.credential.businessAccountId,
        graph_api_version: this.credential.graphApiVersion,
        access_token: this.credential.accessToken || null,
        app_secret: this.credential.appSecret || null,
      })
      this.templateErrors = response.data.template_errors ?? []
      return this.fromJson(response.data.data)
    } catch (e) {
      return new ErrorHandler(e.response?.data?.message ?? e.message, "http")
    }
  }
}
