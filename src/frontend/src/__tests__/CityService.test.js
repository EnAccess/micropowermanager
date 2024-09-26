jest.mock("../repositories/CityRepository")
import { CityService } from "../services/CityService"
const cityService = new CityService()
const cityServiceProperties = [
  "id",
  "name",
  "country_id",
  "created_at",
  "updated_at",
  "cluster_id",
  "mini_grid_id",
]

describe("CityService #getCities", () => {
  it("should get cities data", async () => {
    const data = await cityService.getCities()
    expect(Object.keys(data[0]).length).toEqual(7)
  })
  it("should list cities data with these properties", async () => {
    const data = await cityService.getCities()
    Object.keys(data[0]).forEach(function (item, index) {
      expect(item).toEqual(cityServiceProperties[index])
    })
  })
  it("should not have null data", async () => {
    const data = await cityService.getCities()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).not.toBeNull()
      expect(data[item]).not.toEqual("")
    })
  })
})
describe("CityService #createCity", () => {
  it("should create new City successfully", async () => {
    const testData = require("./TestData/cityCreate.json")
    const data = await cityService.createCity(
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
