jest.mock("../repositories/MapSettingsRepository")
import { MapSettingsService } from "../services/MapSettingsService"
const mapSettingsService = new MapSettingsService()
const mapSettingsProperties = ["id", "zoom", "latitude", "longitude"]

describe("MapSettingsService #list", function () {
  it("should get map settings data", async () => {
    const data = await mapSettingsService.list()
    expect(Object.keys(data).length).not.toEqual(0)
  })
  it("should get map settings data with correct properties", async () => {
    const data = await mapSettingsService.list()
    Object.keys(data).forEach(function (item, index) {
      expect(item).toEqual(mapSettingsProperties[index])
    })
  })
  it("should not get null or undefined map settigs data", async () => {
    const data = await mapSettingsService.list()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).not.toBeNull()
      expect(data[item]).not.toBeUndefined()
    })
  })
})
describe("MapSettingsService #update", () => {
  const mapSettingsUpdateData = require("./TestData/mapSettingsUpdate.json")
  mapSettingsService.mapSettings = mapSettingsUpdateData
  it("should update Map Settings data successfully", async () => {
    const data = await mapSettingsService.update()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).toEqual(mapSettingsService.mapSettings[item])
    })
  })
  it("should create when no data found on database", async () => {
    const data = await mapSettingsService.update()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).not.toBeUndefined()
      expect(data[item]).not.toEqual("")
      expect(data[item]).not.toBeNull()
    })
  })
})
