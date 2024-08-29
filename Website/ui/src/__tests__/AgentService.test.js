jest.mock("../repositories/AgentRepository")
import { AgentService } from "../services/AgentService"
const agentService = new AgentService()
const agentProperties = [
  "id",
  "personId",
  "miniGrid",
  "deviceId",
  "name",
  "surname",
  "email",
  "balance",
  "gender",
  "phone",
  "birthday",
  "commissionType",
  "commissionRevenue",
  "commissionTypeId",
  "dueToEnergySupplier",
]

describe("AgentService #create", () => {
  it("should create a new agent successfully", async () => {
    const agentCreateData = require("./TestData/agentCreate.json")
    agentService.agent = agentCreateData
    const data = await agentService.createAgent()
    expect(Object.keys(data).length).not.toEqual(0)
    Object.keys(data).forEach(function (item) {
      expect(data[item]).not.toBeUndefined()
    })
  })
})
describe("AgentService #updateAgent", () => {
  it("should update agent successfully", async () => {
    const agentUpdateDate = require("./TestData/agentUpdate.json")
    agentService.agent = agentUpdateDate
    const data = await agentService.updateAgent()
    expect(Object.keys(data).length).not.toEqual(0)
    Object.keys(data).forEach(function (item) {
      expect(data[item]).toEqual(agentUpdateDate[item])
    })
  })
})

describe("AgentService #getAgent", () => {
  it("should get specific agent data for details page", async () => {
    const data = await agentService.getAgent(3)
    expect(Object.keys(data).length).not.toEqual(0)
  })
  it("should get agent data with these properties", async () => {
    const data = await agentService.getAgent(3)
    Object.keys(data).forEach(function (item, index) {
      expect(item).toEqual(agentProperties[index])
    })
  })
})
