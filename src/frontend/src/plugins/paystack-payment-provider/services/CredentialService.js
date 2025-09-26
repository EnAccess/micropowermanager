import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/paystack/credential`

export class CredentialService {
  constructor() {
    this.credential = {
      secretKey: "",
      publicKey: "",
      callbackUrl: "",
      merchantName: "",
      environment: "test",
    }
  }

  fromJson(credentialData) {
    this.credential = {
      id: credentialData.id,
      secretKey: credentialData.secret_key,
      publicKey: credentialData.public_key,
      callbackUrl: credentialData.callback_url,
      merchantName: credentialData.merchant_name,
      environment: credentialData.environment,
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
        // Initialize with default values, credential will be created on first save
        return this.credential
      }

      throw error
    }
  }

  async updateCredential() {
    try {
      const credentialPayload = {
        secret_key: this.credential.secretKey,
        public_key: this.credential.publicKey,
        callback_url: this.credential.callbackUrl,
        merchant_name: this.credential.merchantName,
        environment: this.credential.environment,
      }

      const response = await Client.put(`${resource}`, credentialPayload)
      if (response.data && response.data.data) {
        return this.fromJson(response.data.data)
      }
      return response
    } catch (error) {
      console.error("Error updating credential:", error)

      // Handle validation errors from Laravel (422 status)
      if (error.response && error.response.status === 422) {
        const validationErrors = error.response.data.errors
        const errorMessages = []

        // Convert validation errors to user-friendly messages
        for (const field in validationErrors) {
          if (validationErrors.hasOwnProperty(field)) {
            errorMessages.push(...validationErrors[field])
          }
        }

        const combinedMessage = errorMessages.join(", ")
        throw new Error(combinedMessage)
      }

      // Handle other types of errors
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
