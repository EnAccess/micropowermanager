jest.mock("../repositories/AppliancePaymentRepository.js", () => ({}))

import { AppliancePaymentService } from "../services/AppliancePaymentService.js"

describe("AppliancePaymentService payment completion", () => {
  function serviceWith(...statuses) {
    const service = new AppliancePaymentService()
    service.checkPaymentStatus = jest.fn()
    statuses.forEach((status) => {
      service.checkPaymentStatus.mockResolvedValueOnce(status)
    })
    return service
  }

  it("waits for a token even when financial processing finished", async () => {
    const completed = {
      processed: true,
      token_issuance: { status: "completed", message: "Token issued." },
    }
    const service = serviceWith(
      { processed: true, token_issuance: { status: "pending" } },
      completed,
    )
    await expect(
      service.pollPaymentStatus(1, { interval: 0 }),
    ).resolves.toEqual(completed)
    expect(service.checkPaymentStatus).toHaveBeenCalledTimes(2)
  })

  it.each(["blocked", "uncertain"])(
    "surfaces %s issuance without reporting success",
    async (status) => {
      const service = serviceWith({
        processed: true,
        token_issuance: { status, message: "Administrator review required." },
      })
      await expect(service.pollPaymentStatus(1)).rejects.toMatchObject({
        message: "Administrator review required.",
        type: "payment_status",
      })
    },
  )

  it("rejects failed provider payments immediately", async () => {
    const service = serviceWith({ status: "failed", processed: false })
    await expect(service.pollPaymentStatus(1)).rejects.toMatchObject({
      type: "payment_status",
    })
    expect(service.checkPaymentStatus).toHaveBeenCalledTimes(1)
  })

  it("preserves completion for other manufacturers", async () => {
    const completed = { processed: true, token_issuance: null }
    const service = serviceWith(completed)
    await expect(service.pollPaymentStatus(1)).resolves.toEqual(completed)
  })
})
