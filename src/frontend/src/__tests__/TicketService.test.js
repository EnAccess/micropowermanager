jest.mock("../repositories/TicketRepository")
import { TicketService } from "../services/TicketService"
const ticketService = new TicketService()

describe("TicketService #createMaintenanceTicket", function () {
  it("should create a new maintenance ticket successfully", async () => {
    const testData = require("./TestData/maintenanceTicketCreate.json")
    const data = await ticketService.createMaintenanceTicket(testData)
    expect(data[0]).toHaveProperty("ticket_id")
    expect(data[0].ticket_id).not.toBeNull()
    expect(data[0].ticket_id).not.toBeUndefined()
  })
})
