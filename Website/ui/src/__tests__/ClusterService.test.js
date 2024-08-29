import i18n from "../i18n"
jest.mock("../repositories/ClusterRepository")
import { ClusterService } from "../services/ClusterService"
const clusterService = new ClusterService()
const clusterProperties = [
  "id",
  "name",
  "manager_id",
  "created_at",
  "updated_at",
  "meterCount",
  "revenue",
  "population",
]

describe("ClusterService #getClusters", () => {
  it("should get clusters successfully", async () => {
    const data = await clusterService.getClusters()
    expect(Object.keys(data[0]).length).not.toEqual(0)
  })
  it("should list clusters data with the properties", async () => {
    const data = await clusterService.getClusters()
    Object.keys(data[0]).forEach(function (item, index) {
      expect(item).toEqual(clusterProperties[index])
    })
  })
})
describe("ClusterService #createCluster", () => {
  it("should create new cluster successfully", async () => {
    const testData = require("./TestData/clusterCreate.json")
    const data = await clusterService.createCluster(
      testData.geoData,
      testData.geo_type,
      testData.name,
      testData.manager_id,
    )
    expect(data).toHaveProperty("id")
    expect(data.id).not.toBeNull()
    expect(data.id).not.toBeUndefined()
  })
})
describe("ClusterService #getAllRevenues", () => {
  it("should get clusters revenues data for chart ", async () => {
    const data = await clusterService.getAllRevenues("monthly")
    expect(Object.keys(data[0]).length).not.toEqual(0)
  })
})
describe("ClusterService #getClusterCitiesRevenue", () => {
  it("should get cluster cities revenue data for chart", async () => {
    const financialData = await clusterService.getClusterCitiesRevenue(
      12,
      "monthly",
    )
    expect(financialData.length).not.toEqual(0)
  })
})
describe("ClusterService #lineChartData", () => {
  it("should get formatted Line chart Data for Clusters", async () => {
    const financialData = await clusterService.getAllRevenues("monthly")
    const data = clusterService.lineChartData(false)
    expect(data.length).not.toBe(0)
    expect(data[0][0]).toBe(i18n.tc("words.period"))
    expect(financialData[0].name).toEqual(data[0][1])
    for (let i = 0; i < financialData.length; i++) {
      expect(financialData[i].name).toEqual(data[0][i + 1])
      Object.keys(financialData[i].period).forEach(function (item, index) {
        expect(financialData[i].period[item].revenue).toEqual(
          data[index + 1][i + 1],
        )
      })
    }
  })
  it("should get formatted Line chart data for Cluster Mini Grids", async () => {
    const financialData = await clusterService.getClusterCitiesRevenue(
      12,
      "monthly",
    )
    const data = clusterService.lineChartData(false)
    expect(data.length).not.toBe(0)
    expect(data[0][0]).toBe(i18n.tc("words.period"))
    for (let i = 0; i < financialData.length; i++) {
      expect(financialData[i].name).toEqual(data[0][i + 1])
      Object.keys(financialData[i].period).forEach(function (item, index) {
        expect(financialData[i].period[item].revenue).toEqual(
          data[index + 1][i + 1],
        )
      })
    }
  })
})
describe("ClusterService #columnChartData", () => {
  it("should get formatted Column Chart Data for Clusters", async () => {
    const financialData = await clusterService.getAllRevenues("monthly")
    const data = clusterService.columnChartData(false, "cluster")
    expect(data.length).not.toBe(0)
    expect(financialData.length).toEqual(data.length - 1)
    expect(data[0][0]).toBe(i18n.tc("words.cluster"))
    expect(data[0][1]).toBe(i18n.tc("words.revenue"))
    for (let i = 0; i < financialData.length; i++) {
      expect(financialData[i].name).toEqual(data[i + 1][0])
      expect(financialData[i].totalRevenue).toEqual(data[i + 1][1])
    }
  })
  it("should get formatted Column chart data for MiniGrids", async () => {
    const financialData = await clusterService.getClusterCitiesRevenue(
      12,
      "monthly",
    )
    const data = clusterService.columnChartData(false, "miniGrid")
    expect(data[0][0]).toBe(i18n.tc("words.miniGrid"))
    expect(data[0][1]).toBe(i18n.tc("words.revenue"))
    for (let i = 0; i < financialData.length; i++) {
      expect(financialData[i].name).toEqual(data[i + 1][0])
      expect(financialData[i].totalRevenue).toEqual(data[i + 1][1])
    }
  })
})

describe("CLusterService #getClusterTrends", () => {
  it("should get cluster revenue trends data", async () => {
    const trendsData = await clusterService.getClusterTrends()
    expect(clusterService.trendChartData.length).not.toEqual(0)
    expect(Object.keys(trendsData).length).toEqual(
      clusterService.trendChartData.base[0].length - 1,
    )
  })
  it("should get cluster trends data with right properties", async () => {
    const trendsData = await clusterService.getClusterTrends()
    Object.keys(trendsData).forEach(function (item, index) {
      expect(item).toEqual(clusterService.trendChartData.base[0][index + 1])
    })
  })
  it("should get right cluster trends revenue data", async () => {
    const trendsData = await clusterService.getClusterTrends()
    Object.keys(trendsData).forEach(function (item, index) {
      Object.keys(trendsData[item]).forEach(function (it, ind) {
        expect(trendsData[item][it]).toEqual(
          clusterService.trendChartData.base[ind + 1][index + 1],
        )
      })
    })
  })
})
