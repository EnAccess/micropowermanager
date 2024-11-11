jest.mock("../repositories/MiniGridRepository")
import { MiniGridService } from "../services/MiniGridService"
const miniGridService = new MiniGridService()
const miniGridProperties = [
  "id",
  "cluster_id",
  "name",
  "created_at",
  "updated_at",
]

describe("MiniGridService #getMiniGrids", () => {
  beforeEach(() => {
    jest.setTimeout(10000)
  })
  it("should get miniGrids data", async () => {
    const data = await miniGridService.getMiniGrids()
    expect(Object.keys(data[0]).length).toEqual(6)
  })
  it("should list miniGrids data with these properties", async () => {
    const data = await miniGridService.getMiniGrids()
    Object.keys(data[0]).forEach(function (item, index) {
      expect(item).toEqual(miniGridProperties[index])
    })
  })
  it("should not have null data", async () => {
    const data = await miniGridService.getMiniGrids()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).not.toBeNull()
      expect(data[item]).not.toEqual("")
    })
  })
})

describe("MiniGridService #createMiniGrid", () => {
  it("should create new miniGrid successfully", async () => {
    const testData = require("./TestData/miniGridCreate.json")
    const data = await miniGridService.createMiniGrid(
      testData.name,
      testData.cluster_id,
      testData.geo_data,
    )
    expect(data).toHaveProperty("id")
    expect(data.id).not.toBeNull()
    expect(data.id).not.toBeUndefined()
  })
})
