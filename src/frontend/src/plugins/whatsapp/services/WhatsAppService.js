import WhatsAppRepository from "../repositories/WhatsAppRepository.js"

const MISSING_FIELD_LABELS = {
  api_access_token: "API access token",
  phone_number_id: "Phone Number ID",
  whatsapp_business_account_id: "Business Account ID",
}

const toError = (error) => {
  const message =
    error?.response?.data?.message ||
    error?.response?.data?.error ||
    error?.message ||
    "WhatsApp request failed"
  return new Error(message)
}

const settingsFromResponse = (data) => ({
  enabled: Boolean(data.is_enabled),
  apiAccessToken: "",
  phoneNumberId: data.phone_number_id || "",
  businessAccountId: data.whatsapp_business_account_id || "",
  apiVersion: data.api_version || "",
  senderPhoneNumber: data.sender_phone_number || "",
  webhookVerificationToken: "",
  hasApiAccessToken: Boolean(data.has_api_access_token),
  hasWebhookVerificationToken: Boolean(data.has_webhook_verify_token),
  notifications: {
    paymentReceipts: data.notifications?.payment_receipts ?? true,
    lowBalanceWarnings: data.notifications?.low_balance_warnings ?? true,
    tokenDeliveries: data.notifications?.token_deliveries ?? true,
  },
})

const settingsToPayload = (settings) => {
  const payload = {
    phone_number_id: settings.phoneNumberId || null,
    whatsapp_business_account_id: settings.businessAccountId || null,
    sender_phone_number: settings.senderPhoneNumber || null,
    api_version: settings.apiVersion || null,
    is_enabled: Boolean(settings.enabled),
    notification_settings: {
      payment_receipts: settings.notifications?.paymentReceipts ?? true,
      low_balance_warnings: settings.notifications?.lowBalanceWarnings ?? true,
      token_deliveries: settings.notifications?.tokenDeliveries ?? true,
    },
  }

  // The API masks stored secrets, so a blank field means "keep the existing
  // token" rather than "clear it".
  if (settings.apiAccessToken) {
    payload.api_access_token = settings.apiAccessToken
  }
  if (settings.webhookVerificationToken) {
    payload.webhook_verify_token = settings.webhookVerificationToken
  }

  return payload
}

const templateFromResponse = (data) => ({
  id: data.id,
  name: data.name,
  type: data.type,
  language: data.language,
  body: data.body,
  status: data.status,
  updatedAt: data.updated_at,
})

const templateToPayload = (template) => ({
  name: template.name,
  type: template.type,
  language: template.language,
  body: template.body,
  status: template.status || "Draft",
})

const messageFromResponse = (data) => ({
  id: data.id,
  date: data.date,
  customer: data.customer,
  phone: data.phone,
  type: data.type,
  status: data.status,
  externalId: data.external_id,
  reference: data.reference,
  errorMessage: data.error_message,
})

export class WhatsAppService {
  constructor(repository = WhatsAppRepository) {
    this.repository = repository
  }

  async getSettings() {
    try {
      const { data } = await this.repository.getCredential()
      return settingsFromResponse(data)
    } catch (error) {
      throw toError(error)
    }
  }

  async updateSettings(settings) {
    try {
      const { data } = await this.repository.updateCredential(
        settingsToPayload(settings),
      )
      return settingsFromResponse(data)
    } catch (error) {
      throw toError(error)
    }
  }

  async getConnectionStatus() {
    try {
      const { data } = await this.repository.getStatus()
      return {
        configured: Boolean(data.configured),
        connected: Boolean(data.connected),
        missingFields: (data.missing_fields || []).map(
          (field) => MISSING_FIELD_LABELS[field] || field,
        ),
        sender: data.sender || "",
      }
    } catch (error) {
      throw toError(error)
    }
  }

  async getTemplates() {
    try {
      const { data } = await this.repository.getTemplates()
      return (data.data || []).map(templateFromResponse)
    } catch (error) {
      throw toError(error)
    }
  }

  async createTemplate(template) {
    try {
      const { data } = await this.repository.createTemplate(
        templateToPayload(template),
      )
      return templateFromResponse(data.data)
    } catch (error) {
      throw toError(error)
    }
  }

  async updateTemplate(id, template) {
    try {
      const { data } = await this.repository.updateTemplate(
        id,
        templateToPayload(template),
      )
      return templateFromResponse(data.data)
    } catch (error) {
      throw toError(error)
    }
  }

  async getMessages(status) {
    try {
      const { data } = await this.repository.getMessages(status)
      return (data.data || []).map(messageFromResponse)
    } catch (error) {
      throw toError(error)
    }
  }

  async sendTestMessage(phone, message) {
    try {
      const { data } = await this.repository.sendTestMessage({
        recipient_phone: phone,
        message,
      })
      return messageFromResponse(data.data)
    } catch (error) {
      throw toError(error)
    }
  }
}

export const whatsappService = new WhatsAppService()
