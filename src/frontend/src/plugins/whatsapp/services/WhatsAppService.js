const initialSettings = {
  enabled: true,
  apiAccessToken: "",
  phoneNumberId: "",
  businessAccountId: "",
  apiVersion: "v20.0",
  senderPhoneNumber: "",
  webhookVerificationToken: "",
  notifications: {
    paymentReceipts: true,
    lowBalanceWarnings: true,
    tokenDeliveries: true,
  },
}

const initialTemplates = [
  {
    id: 1,
    name: "Payment receipt",
    type: "Payment receipt",
    language: "English",
    status: "Active",
    body: "Hello {{customer_name}}, your payment of {{payment_amount}} was received on {{payment_date}}. Reference: {{transaction_id}}.",
    updatedAt: "2026-09-28",
  },
  {
    id: 2,
    name: "Low balance warning",
    type: "Low-balance warning",
    language: "English",
    status: "Draft",
    body: "Hello {{customer_name}}, your current balance is {{balance}}. Please top up soon.",
    updatedAt: "2026-09-25",
  },
  {
    id: 3,
    name: "Token delivery",
    type: "Token delivery",
    language: "English",
    status: "Active",
    body: "Your token is {{token}}. Thank you, {{customer_name}}.",
    updatedAt: "2026-09-22",
  },
]

const initialMessages = [
  {
    id: 1,
    date: "2026-10-08 14:32",
    customer: "Amara Banda",
    phone: "+258 84 123 4567",
    type: "Payment receipt",
    status: "Delivered",
    externalId: "wamid.HBgL123456",
    reference: "PAY-10428",
  },
  {
    id: 2,
    date: "2026-10-08 13:10",
    customer: "Jonas Mussa",
    phone: "+258 82 987 6543",
    type: "Token delivery",
    status: "Read",
    externalId: "wamid.HBgL123455",
    reference: "TOK-10427",
  },
  {
    id: 3,
    date: "2026-10-08 11:46",
    customer: "Lina Paulo",
    phone: "+258 86 222 9012",
    type: "Low-balance warning",
    status: "Failed",
    externalId: "wamid.HBgL123454",
    reference: "BAL-10426",
  },
  {
    id: 4,
    date: "2026-10-08 09:05",
    customer: "Daniel Chongo",
    phone: "+258 84 555 0199",
    type: "Payment receipt",
    status: "Sent",
    externalId: "wamid.HBgL123453",
    reference: "PAY-10425",
  },
]

const clone = (value) => JSON.parse(JSON.stringify(value))

export class WhatsAppService {
  constructor() {
    this.settings = clone(initialSettings)
    this.templates = clone(initialTemplates)
    this.messages = clone(initialMessages)
  }

  async getSettings() {
    return clone(this.settings)
  }

  async updateSettings(settings) {
    this.settings = {
      ...this.settings,
      ...clone(settings),
      notifications: {
        ...this.settings.notifications,
        ...(settings.notifications || {}),
      },
    }
    return this.getSettings()
  }

  async getConnectionStatus() {
    const requiredFields = [
      ["apiAccessToken", "API access token"],
      ["phoneNumberId", "Phone Number ID"],
      ["businessAccountId", "Business Account ID"],
    ]
    const missingFields = requiredFields
      .filter(([key]) => !this.settings[key])
      .map(([, label]) => label)
    const configured = missingFields.length === 0
    return {
      configured,
      connected: configured && this.settings.enabled,
      missingFields,
      sender: this.settings.senderPhoneNumber || "",
    }
  }

  async getTemplates() {
    return clone(this.templates)
  }

  async createTemplate(template) {
    const created = {
      ...clone(template),
      id: Date.now(),
      status: "Draft",
      updatedAt: new Date().toISOString().slice(0, 10),
    }
    this.templates.push(created)
    return clone(created)
  }

  async updateTemplate(id, template) {
    const index = this.templates.findIndex((item) => item.id === id)
    if (index === -1) {
      throw new Error("Template not found")
    }
    this.templates.splice(index, 1, {
      ...this.templates[index],
      ...clone(template),
      updatedAt: new Date().toISOString().slice(0, 10),
    })
    return clone(this.templates[index])
  }

  async getMessages() {
    return clone(this.messages)
  }

  async sendTestMessage(phone, message) {
    if (!phone || !message) {
      throw new Error("Phone number and message are required")
    }
    const created = {
      id: Date.now(),
      date: new Date().toISOString().slice(0, 16).replace("T", " "),
      customer: "Test recipient",
      phone,
      type: "Test message",
      status: "Queued",
      externalId: "mock-" + Date.now(),
      reference: "TEST-" + Date.now(),
    }
    this.messages.unshift(created)
    return clone(created)
  }
}

export const whatsappService = new WhatsAppService()
