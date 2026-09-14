import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/flutterwave/credential`

export class CredentialService {
  constructor() {
    this.credential = {
      // publicKey/secretKey/encryptionKey are write-only from the form's
      // perspective — the backend never returns the stored values, so these
      // stay "" unless the operator types a new value. Blank on submit means
      // "keep current".
      publicKey: "",
      secretKey: "",
      encryptionKey: "",
      webhookSecretHash: "",
      publicKeySet: false,
      secretKeySet: false,
      encryptionKeySet: false,
      webhookSecretHashSet: false,
      callbackUrl: "",
      merchantName: "",
      merchantEmail: "",
      environment: "test",
    }
  }

  fromJson(credentialData) {
    this.credential = {
      id: credentialData.id,
      publicKey: "",
      secretKey: "",
      encryptionKey: "",
      webhookSecretHash: "",
      publicKeySet: Boolean(credentialData.public_key_set),
      secretKeySet: Boolean(credentialData.secret_key_set),
      encryptionKeySet: Boolean(credentialData.encryption_key_set),
      webhookSecretHashSet: Boolean(credentialData.webhook_secret_hash_set),
      callbackUrl: credentialData.callback_url,
      merchantName: credentialData.merchant_name,
      merchantEmail: credentialData.merchant_email,
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
        callback_url: this.credential.callbackUrl,
        merchant_name: this.credential.merchantName,
        merchant_email: this.credential.merchantEmail,
        environment: this.credential.environment,
      }
      // Only send a key when the operator actually typed something — blank
      // fields tell the backend to keep the stored values.
      if (this.credential.publicKey) {
        credentialPayload.public_key = this.credential.publicKey
      }
      if (this.credential.secretKey) {
        credentialPayload.secret_key = this.credential.secretKey
      }
      if (this.credential.encryptionKey) {
        credentialPayload.encryption_key = this.credential.encryptionKey
      }
      if (this.credential.webhookSecretHash) {
        credentialPayload.webhook_secret_hash =
          this.credential.webhookSecretHash
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
          if (Object.prototype.hasOwnProperty.call(validationErrors, field)) {
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
