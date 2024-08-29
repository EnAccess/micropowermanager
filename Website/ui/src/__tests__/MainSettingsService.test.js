jest.mock("../repositories/MainSettingsRepository")
import { MainSettingsService } from "../services/MainSettingsService"
const mainSettings = new MainSettingsService()
const mainSettingsProperties = [
  "id",
  "siteTitle",
  "companyName",
  "currency",
  "country",
  "language",
  "vatEnergy",
  "vatAppliance",
]
describe("MainSettingsService #List", () => {
  beforeEach(() => {
    jest.setTimeout(10000)
  })
  it("should get main settings data", async () => {
    const data = await mainSettings.list()
    expect(Object.keys(data).length).toEqual(8)
  })
  it("should list main settings data with these properties", async () => {
    const data = await mainSettings.list()
    Object.keys(data).forEach(function (item, index) {
      expect(item).toEqual(mainSettingsProperties[index])
    })
  })
  it("should not get null or undefined Main Settings data", async () => {
    const data = await mainSettings.list()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).not.toBeNull()
      expect(data[item]).not.toEqual("")
    })
  })
})
describe("MainSettingsService #Update", () => {
  const mainSettingsUpdateData = require("./TestData/mainSettingsUpdate.json")
  mainSettings.mainSettings = mainSettingsUpdateData
  it("should update main settings successfully", async () => {
    const data = await mainSettings.update()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).toEqual(mainSettings.mainSettings[item])
    })
  })
  it("should create when no data found on database", async () => {
    const data = await mainSettings.update()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).not.toBeUndefined()
      expect(data[item]).not.toEqual("")
      expect(data[item]).not.toBeNull()
    })
  })
})
