jest.mock("../repositories/TargetRepository")
import { Targets } from "@/services/TargetService"
const targets = new Targets()

describe("TargetsService #store", () => {
  it("should create a new target successfully ", async () => {
    const testData = require("./TestData/targetCreate.json")
    const data = await targets.store(testData)
    expect(data.data.id).toEqual(testData.targetId)
  })
})
