import { ErrorHandler } from "@/Helpers/ErrorHandler"
import i18n from "../i18n"
import { convertObjectKeysToSnakeCase } from "@/Helpers/Utils"
import ClusterRepository from "@/repositories/ClusterRepository"

export class ClusterService {
  constructor() {
    this.repository = ClusterRepository
    this.clusters = []
    this.financialData = []
    this.clusterTrends = []
    this.trendChartData = { base: null, overview: null }
    this.list = []
  }

  async createCluster(clusterData) {
    const params = convertObjectKeysToSnakeCase(clusterData)
    try {
      const { data, status, error } = await this.repository.create(params)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getClusters() {
    try {
      const { data, status, error } = await this.repository.list()
      if (status !== 200) return new ErrorHandler(error, "http", status)
      this.clusters = data.data
      this.list = data.data

      return this.clusters
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getClusterGeoLocation(clusterId) {
    try {
      const { data, status, error } =
        await this.repository.getGeoLocation(clusterId)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getDetails(clusterId) {
    try {
      const { data, status, error } = await this.repository.get(clusterId)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getClusterRevenues(clusterId) {
    try {
      const { data, status, error } =
        await this.repository.getClusterRevenues(clusterId)
      if (status !== 200) return new ErrorHandler(error, "http", status)

      return data.data
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  async getAllRevenues(period, startDate, endDate) {
    const queryString = `?period=${period}&startDate=${
      startDate ?? ""
    }&endDate=${endDate ?? ""}`
    try {
      const { data, status, error } =
        await this.repository.getAllRevenues(queryString)
      if (status !== 200 && status !== 201)
        return new ErrorHandler(error, "http", status)

      this.financialData = data.data
      return this.financialData
    } catch (e) {
      const errorMessage = e.response.data.message
      return new ErrorHandler(errorMessage, "http")
    }
  }

  fillTrends() {
    let trendKeys = Object.keys(this.clusterTrends)
    this.trendChartData.base = [Object.keys(this.clusterTrends)]
    this.trendChartData.base[0].unshift("Date")
    for (let i in this.clusterTrends[trendKeys[0]]) {
      let tmpData = []
      for (let j in trendKeys) {
        tmpData.push(this.clusterTrends[trendKeys[j]][i])
      }
      tmpData.unshift(i)
      this.trendChartData.base.push(tmpData)
    }
  }

  insertCityNames(count, data) {
    for (let i = 0; i < count; i++) {
      data.push(this.financialData[i].name)
    }
    return data
  }

  lineChartData(summary) {
    let data = []
    data.push([i18n.tc("words.period")])

    let itemCount = this.financialData.length
    if (itemCount === 0) {
      return
    }

    data[0] = this.insertCityNames(itemCount, data[0])
    if (summary) {
      data[0].push(i18n.tc("words.total"))
    }

    let periods = this.financialData[0].period
    for (let p in periods) {
      data.push(this.getPeriodicData(itemCount, p, summary))
    }
    return data
  }

  getPeriodicData(count, periodName, summary) {
    let data = []
    let sum = 0
    data.push(periodName)
    for (let i = 0; i < count; i++) {
      if (summary) {
        sum += this.financialData[i].period[periodName].revenue
      }
      data.push(this.financialData[i].period[periodName].revenue)
    }
    if (summary) {
      data.push(sum)
    }
    return data
  }

  columnChartData(summary, type) {
    let data = []
    let summaryRevenue = 0
    let infoData =
      type === "cluster" ? i18n.tc("words.cluster") : i18n.tc("words.miniGrid")
    data.push([infoData, i18n.tc("words.revenue")])
    for (let i in this.financialData) {
      let cD = this.financialData[i]
      if (summary) {
        summaryRevenue += cD.totalRevenue
      }
      data.push([cD.name, cD.totalRevenue])
    }
    if (summary) {
      data.push(["Sum", summaryRevenue])
    }
    return data
  }
}
