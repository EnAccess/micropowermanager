import { WhatsAppService } from "../plugins/whatsapp/services/WhatsAppService.js"

describe("WhatsAppService", () => {
  it("returns safe mock settings without credentials", async () => {
    const service = new WhatsAppService()
    const settings = await service.getSettings()
    const connection = await service.getConnectionStatus()

    expect(settings.apiAccessToken).toBe("")
    expect(settings.webhookVerificationToken).toBe("")
    expect(settings.notifications.paymentReceipts).toBe(true)
    expect(connection.configured).toBe(false)
    expect(connection.missingFields).toEqual([
      "API access token",
      "Phone Number ID",
      "Business Account ID",
    ])
  })

  it("reports a configured connection after required details are saved", async () => {
    const service = new WhatsAppService()
    await service.updateSettings({
      apiAccessToken: "mock-token",
      phoneNumberId: "mock-phone-id",
      businessAccountId: "mock-business-id",
      senderPhoneNumber: "+258 840000000",
    })

    await expect(service.getConnectionStatus()).resolves.toMatchObject({
      configured: true,
      connected: true,
      missingFields: [],
      sender: "+258 840000000",
    })
  })

  it("creates and updates templates", async () => {
    const service = new WhatsAppService()
    const created = await service.createTemplate({
      name: "Test",
      body: "{{token}}",
    })
    const updated = await service.updateTemplate(created.id, {
      status: "Active",
    })

    expect(updated.name).toBe("Test")
    expect(updated.status).toBe("Active")
  })

  it("queues a mocked test message", async () => {
    const service = new WhatsAppService()
    const message = await service.sendTestMessage("+258 840000000", "Hello")

    expect(message.status).toBe("Queued")
    expect((await service.getMessages())[0].id).toBe(message.id)
  })
})
