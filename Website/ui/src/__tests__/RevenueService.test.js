jest.mock("../repositories/RevenueRepository")
import { RevenueService } from "../services/RevenueService"
const revenueService = new RevenueService()

describe("RevenueService #getTicketsData", () => {
  it("should get tickets categories for chart", async () => {
    const data = await revenueService.getTicketsData()
    expect(revenueService.openedTicketChartData[0].length).toEqual(
      data.categories.length * 2 + 1,
    )
  })
  it("should get open and closed tickets data for tickets trend chart", async () => {
    const data = await revenueService.getTicketsData()
    Object.keys(data).forEach(function (item, index) {
      if (item !== "categories") {
        Object.keys(data[item]).forEach(function (sub, i) {
          expect(data[item][sub]["opened"]).toEqual(
            revenueService.openedTicketChartData[index * 2 - 1][i * 2 + 1],
          )
          expect(data[item][sub]["closed"]).toEqual(
            revenueService.openedTicketChartData[index * 2][i * 2 + 1],
          )
        })
      }
    })
  })
})
describe("RevenueService #getMiniGridRevenueTrends", () => {
  it("should get revenue trends for specific mini grid", async () => {
    const revenueData = await revenueService.getMiniGridRevenueTrends()
    expect(Object.keys(revenueData).length).not.toEqual(0)
  })
})
describe("RevenueService #fillRevenueTrendsOverView", () => {
  it("should get formatted data for revenue trend overview chart", async () => {
    const revenueData = await revenueService.getMiniGridRevenueTrends()
    const chartData = revenueService.fillRevenueTrendsOverView()
    Object.keys(revenueData).forEach(function (item, index) {
      expect(item).toEqual(chartData[index + 1][0])
      Object.keys(revenueData[item]).forEach(function (sub, i) {
        expect(sub).toEqual(chartData[0][i + 1])
        expect(revenueData[item][sub]["revenue"]).toEqual(
          chartData[index + 1][i + 1],
        )
      })
    })
  })
})
describe("RevenueService #fillRevenueTrends", () => {
  it("should get formatted data for revenue trends chart ", async () => {
    const revenueData = await revenueService.getMiniGridRevenueTrends()
    const chartData = revenueService.fillRevenueTrends("monthly")
    Object.keys(revenueData).forEach(function (item, index) {
      expect(item).toEqual(chartData[index + 1][0])
      Object.keys(revenueData[item]).forEach(function (sub, i) {
        expect(sub).toEqual(chartData[0][i + 1])
        expect(revenueData[item][sub]["revenue"]).toEqual(
          chartData[index + 1][i + 1],
        )
      })
    })
  })
})
