import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/safaricom/credential`

export class CredentialService {
  constructor() {
    this.credential = {
      consumerKey: "",
      consumerSecret: "",
      passkey: "",
      consumerKeySet: false,
      consumerSecretSet: false,
      passkeySet: false,
      shortcode: "",
      environment: "sandbox",
      validationUrl: "",
      confirmationUrl: "",
      timeoutUrl: "",
      resultUrl: "",
    }
  }

  fromJson(credentialData) {
    this.credential = {
      id: credentialData.id,
      consumerKey: "",
      consumerSecret: "",
      passkey: "",
      consumerKeySet: Boolean(credentialData.consumer_key_set),
      consumerSecretSet: Boolean(credentialData.consumer_secret_set),
      passkeySet: Boolean(credentialData.passkey_set),
      shortcode: credentialData.shortcode || "",
      environment: credentialData.environment || "sandbox",
      validationUrl: credentialData.validation_url || "",
      confirmationUrl: credentialData.confirmation_url || "",
      timeoutUrl: credentialData.timeout_url || "",
      resultUrl: credentialData.result_url || "",
    }
    return this.credential
  }

  async getCredential() {
    try {
      const response = await Client.get(`${resource}`)
      if (response.data && response.data.data) {
        return this.fromJson(response.data.data)
      }
    } catch (error) {
      console.error("Error fetching credential:", error)
      if (error.response && error.response.status === 404) {
        return this.credential
      }

      throw error
    }
  }

  async updateCredential() {
    try {
      const payload = {
        shortcode: this.credential.shortcode,
        environment: this.credential.environment,
        validation_url: this.credential.validationUrl || null,
        confirmation_url: this.credential.confirmationUrl || null,
        timeout_url: this.credential.timeoutUrl || null,
        result_url: this.credential.resultUrl || null,
      }
      if (this.credential.consumerKey) {
        payload.consumer_key = this.credential.consumerKey
      }
      if (this.credential.consumerSecret) {
        payload.consumer_secret = this.credential.consumerSecret
      }
      if (this.credential.passkey) {
        payload.passkey = this.credential.passkey
      }

      const response = await Client.put(`${resource}`, payload)
      if (response.data && response.data.data) {
        return this.fromJson(response.data.data)
      }
      return response
    } catch (error) {
      console.error("Error updating credential:", error)

      if (error.response && error.response.status === 422) {
        const validationErrors = error.response.data.errors
        const errorMessages = []
        for (const field in validationErrors) {
          if (Object.prototype.hasOwnProperty.call(validationErrors, field)) {
            errorMessages.push(...validationErrors[field])
          }
        }
        throw new Error(errorMessages.join(", "))
      }

      if (
        error.response &&
        error.response.data &&
        error.response.data.message
      ) {
        throw new Error(error.response.data.message)
      }

      throw error
    }
  }
}
