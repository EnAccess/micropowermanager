jest.mock("../repositories/TariffRepository")
import { TariffService } from "../services/TariffService"
const tariffService = new TariffService()
const tariffProperties = [
  "id",
  "name",
  "price",
  "currency",
  "factor",
  "accessRate",
  "socialTariff",
  "components",
  "tous",
]

describe("TariffService #getTariffs", () => {
  it("should get tariffs data", async () => {
    const data = await tariffService.getTariffs()
    expect(Object.keys(data[0]).length).toEqual(9)
  })
  it("should list tariffs data with these properties", async () => {
    const data = await tariffService.getTariffs()
    Object.keys(data[0]).forEach(function (item, index) {
      expect(item).toEqual(tariffProperties[index])
    })
  })
  it("should not have null data", async () => {
    const data = await tariffService.getTariffs()
    Object.keys(data).forEach(function (item) {
      expect(data[item]).not.toBeNull()
      expect(data[item]).not.toEqual("")
    })
  })
})
describe("TariffService #saveTariff", () => {
  const testData = require("./TestData/tariffCreate.json")
  tariffService.tariff.name = testData.name
  tariffService.tariff.price = testData.price
  tariffService.currency = testData.currency
  tariffService.tariff.factor = testData.factor
  tariffService.tariff.components = testData.components
  tariffService.tariff.accessRate = testData.accessRate
  tariffService.tariff.tous = testData.tous
  it("should create new tariff when method is create", async () => {
    const method = "create"
    const data = await tariffService.saveTariff(method)
    expect(data.id).toEqual(tariffService.tariff.id)
    expect(data.id).not.toBeNull()
    expect(data.id).not.toBeUndefined()
  })
  it("should update tariff when method is update", async () => {
    const method = "update"
    const data = await tariffService.saveTariff(method)
    expect(data.id).toEqual(tariffService.tariff.id)
    expect(data.id).not.toBeNull()
    expect(data.id).not.toBeUndefined()
  })
})
