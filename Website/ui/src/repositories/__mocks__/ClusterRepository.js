const clusterListResponse = require("./responses/cluster/clusterList.json")
const clusterCreateResponse = require("./responses/cluster/clusterCreate.json")
const clustersRevenueResponse = require("./responses/cluster/clustersRevenue.json")
const clusterMiniGridRevenueResponse = require("./responses/cluster/clusterMiniGridsRevenue.json")
const clusterTrendsResponse = require("./responses/cluster/clusterTrends.json")

export default {
  list() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(clusterListResponse))
    })
  },
  create() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(clusterCreateResponse))
    })
  },
  getAllRevenues() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(clustersRevenueResponse))
    })
  },
  getClusterCitiesRevenue() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(clusterMiniGridRevenueResponse))
    })
  },
  getClusterTrends() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(clusterTrendsResponse))
    })
  },
}
