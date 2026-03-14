jest.mock("../repositories/VillageRepository.js")
import { VillageService } from "../services/VillageService.js"
const villageService = new VillageService()
const villageServiceProperties = [
  "id",
  "name",
  "country_id",
  "created_at",
  "updated_at",
  "cluster_id",
  "mini_grid_id",
]

describe("VillageService #getVillages", () => {
  it("should get villages data", async () => {
    const data = await villageService.getVillages()
    expect(Object.keys(data[0]).length).toEqual(7)
  })
  it("should list villages data with these properties", async () => {
    const data = await villageService.getVillages()
    Object.keys(data[0]).forEach(function (item, index) {
      expect(item).toEqual(villageServiceProperties[index])
    })
  })
  it("should not have null data", async () => {
    const data = await villageService.getVillages()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).not.toBeNull()
      expect(data[item]).not.toEqual("")
    })
  })
})
describe("VillageService #createVillage", () => {
  it("should create new Village successfully", async () => {
    const testData = require("./TestData/villageCreate.json")
    const data = await villageService.createVillage(
      testData.name,
      testData.cluster_id,
      testData.mini_grid_id,
      testData.geo_data,
    )
    expect(data).toHaveProperty("id")
    expect(data.id).not.toBeNull()
    expect(data.id).not.toBeUndefined()
  })
})
