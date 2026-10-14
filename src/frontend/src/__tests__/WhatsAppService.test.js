jest.mock("../plugins/whatsapp/repositories/WhatsAppRepository.js", () => ({
  __esModule: true,
  default: {
    getCredential: jest.fn(),
    updateCredential: jest.fn(),
    getStatus: jest.fn(),
    getTemplates: jest.fn(),
    createTemplate: jest.fn(),
    updateTemplate: jest.fn(),
    getMessages: jest.fn(),
    sendTestMessage: jest.fn(),
  },
}))

import WhatsAppRepository from "../plugins/whatsapp/repositories/WhatsAppRepository.js"
import { WhatsAppService } from "../plugins/whatsapp/services/WhatsAppService.js"

const credentialResponse = {
  is_enabled: true,
  phone_number_id: "1098234982374982",
  whatsapp_business_account_id: "987234982374982",
  sender_phone_number: "+258840000000",
  api_version: "v21.0",
  api_access_token: "••••••••",
  has_api_access_token: true,
  has_webhook_verify_token: false,
  notifications: {
    payment_receipts: true,
    low_balance_warnings: false,
    token_deliveries: true,
  },
}

describe("WhatsAppService", () => {
  let service

  beforeEach(() => {
    jest.clearAllMocks()
    service = new WhatsAppService()
  })

  it("maps stored credentials to camelCase settings", async () => {
    WhatsAppRepository.getCredential.mockResolvedValue({
      data: credentialResponse,
    })

    const settings = await service.getSettings()

    expect(settings.phoneNumberId).toBe("1098234982374982")
    expect(settings.businessAccountId).toBe("987234982374982")
    expect(settings.apiAccessToken).toBe("")
    expect(settings.hasApiAccessToken).toBe(true)
    expect(settings.notifications).toEqual({
      paymentReceipts: true,
      lowBalanceWarnings: false,
      tokenDeliveries: true,
    })
  })

  it("sends snake_case payload and omits blank secrets on update", async () => {
    WhatsAppRepository.updateCredential.mockResolvedValue({
      data: credentialResponse,
    })

    await service.updateSettings({
      enabled: true,
      phoneNumberId: "1098234982374982",
      businessAccountId: "987234982374982",
      apiVersion: "v21.0",
      senderPhoneNumber: "+258840000000",
      apiAccessToken: "",
      notifications: {
        paymentReceipts: true,
        lowBalanceWarnings: false,
        tokenDeliveries: true,
      },
    })

    expect(WhatsAppRepository.updateCredential).toHaveBeenCalledWith(
      expect.objectContaining({
        phone_number_id: "1098234982374982",
        whatsapp_business_account_id: "987234982374982",
        is_enabled: true,
        notification_settings: {
          payment_receipts: true,
          low_balance_warnings: false,
          token_deliveries: true,
        },
      }),
    )
    const payload = WhatsAppRepository.updateCredential.mock.calls[0][0]
    expect(payload.api_access_token).toBeUndefined()
  })

  it("maps missing field keys to display labels in connection status", async () => {
    WhatsAppRepository.getStatus.mockResolvedValue({
      data: {
        configured: false,
        connected: false,
        missing_fields: ["api_access_token", "phone_number_id"],
        sender: "",
      },
    })

    const status = await service.getConnectionStatus()

    expect(status.configured).toBe(false)
    expect(status.missingFields).toEqual([
      "API access token",
      "Phone Number ID",
    ])
  })

  it("lists templates from the API envelope", async () => {
    WhatsAppRepository.getTemplates.mockResolvedValue({
      data: {
        data: [
          {
            id: 1,
            name: "Payment receipt",
            type: "Payment receipt",
            language: "en",
            body: "Hello {{customer_name}}",
            status: "Active",
            updated_at: "2026-09-28",
          },
        ],
      },
    })

    const templates = await service.getTemplates()

    expect(templates).toEqual([
      {
        id: 1,
        name: "Payment receipt",
        type: "Payment receipt",
        language: "en",
        body: "Hello {{customer_name}}",
        status: "Active",
        updatedAt: "2026-09-28",
      },
    ])
  })

  it("creates a template and maps the response", async () => {
    WhatsAppRepository.createTemplate.mockResolvedValue({
      data: {
        data: {
          id: 5,
          name: "Test",
          type: "Payment receipt",
          language: "en",
          body: "{{token}}",
          status: "Draft",
          updated_at: "2026-10-14",
        },
      },
    })

    const created = await service.createTemplate({
      name: "Test",
      type: "Payment receipt",
      language: "en",
      body: "{{token}}",
    })

    expect(WhatsAppRepository.createTemplate).toHaveBeenCalledWith({
      name: "Test",
      type: "Payment receipt",
      language: "en",
      body: "{{token}}",
      status: "Draft",
    })
    expect(created).toMatchObject({
      id: 5,
      status: "Draft",
      updatedAt: "2026-10-14",
    })
  })

  it("maps message history including failure reason", async () => {
    WhatsAppRepository.getMessages.mockResolvedValue({
      data: {
        data: [
          {
            id: 3,
            date: "2026-10-08 11:46",
            customer: "Lina Paulo",
            phone: "+258862229012",
            type: "Low-balance warning",
            status: "Failed",
            external_id: null,
            reference: "appliance_rate:9:overdue",
            error_message: "Customer has no valid WhatsApp phone number.",
          },
        ],
      },
    })

    const messages = await service.getMessages()

    expect(messages[0]).toMatchObject({
      id: 3,
      externalId: null,
      reference: "appliance_rate:9:overdue",
      errorMessage: "Customer has no valid WhatsApp phone number.",
    })
  })

  it("sends a test message using the recipient phone field", async () => {
    WhatsAppRepository.sendTestMessage.mockResolvedValue({
      data: {
        data: {
          id: 9,
          date: "2026-10-14 12:00",
          customer: null,
          phone: "258840000000",
          type: "Test message",
          status: "Sent",
          external_id: "wamid.TEST",
          reference: null,
        },
      },
    })

    const message = await service.sendTestMessage("+258840000000", "Hello")

    expect(WhatsAppRepository.sendTestMessage).toHaveBeenCalledWith({
      recipient_phone: "+258840000000",
      message: "Hello",
    })
    expect(message.externalId).toBe("wamid.TEST")
  })

  it("surfaces API error messages", async () => {
    WhatsAppRepository.getCredential.mockRejectedValue({
      response: { data: { message: "Service unavailable" } },
    })

    await expect(service.getSettings()).rejects.toThrow("Service unavailable")
  })
})
