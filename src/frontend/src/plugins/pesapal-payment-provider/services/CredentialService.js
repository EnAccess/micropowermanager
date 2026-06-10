import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/pesapal/credential`

export class CredentialService {
  constructor() {
    this.credential = {
      // consumerKey/consumerSecret are write-only from the form's perspective —
      // the backend never returns the stored values, so this stays "" unless
      // the operator types a new value. Blank on submit means "keep current".
      consumerKey: "",
      consumerSecret: "",
      consumerKeySet: false,
      consumerSecretSet: false,
      callbackUrl: "",
      merchantName: "",
      merchantEmail: "",
      environment: "test",
      currency: "KES",
      ipnId: null,
      ipnRegisteredAt: null,
    }
  }

  fromJson(credentialData) {
    this.credential = {
      id: credentialData.id,
      consumerKey: "",
      consumerSecret: "",
      consumerKeySet: Boolean(credentialData.consumer_key_set),
      consumerSecretSet: Boolean(credentialData.consumer_secret_set),
      callbackUrl: credentialData.callback_url,
      merchantName: credentialData.merchant_name,
      merchantEmail: credentialData.merchant_email,
      environment: credentialData.environment,
      currency: credentialData.currency || "KES",
      ipnId: credentialData.ipn_id,
      ipnRegisteredAt: credentialData.ipn_registered_at,
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
      const credentialPayload = {
        callback_url: this.credential.callbackUrl,
        merchant_name: this.credential.merchantName,
        merchant_email: this.credential.merchantEmail,
        environment: this.credential.environment,
        currency: this.credential.currency,
      }
      // Only send key/secret when the operator actually typed something —
      // blank fields tell the backend to keep the stored values.
      if (this.credential.consumerKey) {
        credentialPayload.consumer_key = this.credential.consumerKey
      }
      if (this.credential.consumerSecret) {
        credentialPayload.consumer_secret = this.credential.consumerSecret
      }

      const response = await Client.put(`${resource}`, credentialPayload)
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

        const combinedMessage = errorMessages.join(", ")
        throw new Error(combinedMessage)
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

  async getPublicUrls() {
    try {
      const response = await Client.get(`${resource}/public-urls`)
      return response.data
    } catch (error) {
      console.error("Error fetching public URLs:", error)
      throw error
    }
  }

  async generateAgentPaymentUrl(customerId = null, agentId = null) {
    try {
      const response = await Client.post(`${resource}/agent-payment-url`, {
        customer_id: customerId,
        agent_id: agentId,
      })
      return response.data
    } catch (error) {
      console.error("Error generating agent payment URL:", error)
      throw error
    }
  }
}
