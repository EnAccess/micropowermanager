jest.mock("../plugins/whatsapp-business-api/repositories/WhatsappRepository.js", () => ({
  __esModule: true,
  default: {
    getCredentials: jest.fn().mockResolvedValue({ data: { phone_number_id: "123" } }),
    updateCredentials: jest.fn().mockImplementation((creds) => Promise.resolve({ data: creds })),
    getOptIns: jest.fn().mockResolvedValue({ data: { data: [{ id: 1, name: "Alice", opted_in: true }], meta: { total: 1 } } }),
    getOptIn: jest.fn().mockImplementation((personId) => Promise.resolve({ data: { person_id: personId, opted_in: true } })),
    updateOptIn: jest.fn().mockImplementation((personId, data) => Promise.resolve({ data: { person_id: personId, opted_in: data.opted_in } })),
    getOptInStats: jest.fn().mockResolvedValue({ data: { total_customers: 10, opted_in: 8, opted_out: 2, pending: 0 } }),
    bulkOptIn: jest.fn().mockImplementation((data) => Promise.resolve({ data: { count: 10, opted_in: data.opted_in } })),
  },
}))

import { WhatsappService } from "../plugins/whatsapp-business-api/services/WhatsappService.js"

describe("WhatsappService", () => {
  let service

  beforeEach(() => {
    service = new WhatsappService()
  })

  it("should get credentials", async () => {
    const res = await service.getCredentials()
    expect(res.data.phone_number_id).toBe("123")
  })

  it("should update credentials", async () => {
    const res = await service.updateCredentials({ phone_number_id: "456" })
    expect(res.data.phone_number_id).toBe("456")
  })

  it("should list customers with opt-in status", async () => {
    const res = await service.getOptIns({ page: 1 })
    expect(res.data.data.length).toBe(1)
    expect(res.data.data[0].name).toBe("Alice")
  })

  it("should get customer opt-in status", async () => {
    const res = await service.getOptIn(42)
    expect(res.data.person_id).toBe(42)
    expect(res.data.opted_in).toBe(true)
  })

  it("should update customer opt-in status", async () => {
    const res = await service.updateOptIn(42, { opted_in: false })
    expect(res.data.person_id).toBe(42)
    expect(res.data.opted_in).toBe(false)
  })

  it("should get opt-in statistics", async () => {
    const res = await service.getOptInStats()
    expect(res.data.total_customers).toBe(10)
    expect(res.data.opted_in).toBe(8)
    expect(res.data.opted_out).toBe(2)
  })

  it("should perform bulk opt-in", async () => {
    const res = await service.bulkOptIn({ opted_in: true, opt_in_all: true })
    expect(res.data.count).toBe(10)
    expect(res.data.opted_in).toBe(true)
  })
})
