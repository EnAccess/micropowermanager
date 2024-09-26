jest.mock("../repositories/TicketTrelloRepository")
import { TicketTrelloService } from "../services/TicketTrelloService"
const ticketTrelloService = new TicketTrelloService()
const ticketDataProperties = [
  "created",
  "id",
  "name",
  "description",
  "due",
  "closed",
  "lastActivity",
  "comments",
  "category",
  "owner",
  "assigned",
]

describe("TicketTrelloService #getTicketDetail", function () {
  const testData = require("./TestData/ticketDetail.json")
  it("should get ticket detail correctly", async () => {
    const data = await ticketTrelloService.getTicketDetail(testData)
    expect(data.id).toEqual(testData.ticket_id)
  })
  it("should get ticket data these properties", async () => {
    const data = await ticketTrelloService.getTicketDetail(testData)
    Object.keys(data).forEach(function (item, index) {
      expect(item).toEqual(ticketDataProperties[index])
    })
  })
})
