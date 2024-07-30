jest.mock("../repositories/BatchRevenueRepository")
import { BatchRevenueService } from "../services/BatchRevenueService"
const batchRevenueService = new BatchRevenueService()
const batchRevenueProperties = [
  "target",
  "total_connections",
  "new_connections",
  "revenue",
  "averages",
]

describe("BatchRevenueService #getRevenueForPeriod", () => {
  it("should get Batch Revenues data successfully", async () => {
    const data = await batchRevenueService.getRevenueForPeriod()
    expect(Object.keys(data).length).not.toEqual(0)
  })
  it("should get formatted data for Revenue Analysis", async () => {
    const data = await batchRevenueService.getRevenueForPeriod()
    Object.keys(data).forEach(function (item) {
      expect(item).toEqual("revenueList")
      Object.keys(data[item]).forEach(function (sub, index) {
        expect(sub).toEqual(batchRevenueProperties[index])
      })
    })
  })
})
describe("BatchRevenueService #initializeDonutCharts", () => {
  it("should get initialized Batch Revenue data for donut chart", async () => {
    const initValue = ["connection", "revenue"]
    const batchData = await batchRevenueService.getRevenueForPeriod()
    const donutChartData = await batchRevenueService.initializeDonutCharts(
      initValue,
      batchData,
    )
    const data = batchData.revenueList.revenue
    Object.keys(data).forEach(function (item, index) {
      expect(item).toEqual(donutChartData[index + 1][0])
      expect(data[item]).toEqual(donutChartData[index + 1][1])
    })
  })
})
describe("BatchRevenueService #initializeColumnChart", () => {
  it("should get initialized batch revenue data for revenue target per customer type", async () => {
    const batchRevenueData = await batchRevenueService.getRevenueForPeriod()
    const columnData =
      await batchRevenueService.initializeColumnChart(batchRevenueData)
    const targets = batchRevenueData.revenueList.target.targets
    Object.keys(targets).forEach(function (item, index) {
      expect(item).toEqual(columnData[index + 1][0])
      expect(columnData[index + 1][1]).not.toBeUndefined()
      expect(columnData[index + 1][1]).not.toBeNull()
      expect(columnData[index + 1][1]).not.toBeNaN()
    })
  })
})
