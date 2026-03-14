import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/clusters`

export default {
  create(cluster) {
    return Client.post(`${resource}`, cluster)
  },
  list() {
    return Client.get(`${resource}`)
  },
  getGeoLocation(clusterId) {
    return Client.get(`${resource}/${clusterId}/geo`)
  },
  get(clusterId) {
    return Client.get(`${resource}/${clusterId}`)
  },
  getClusterRevenues(clusterId) {
    return Client.get(`${resource}/${clusterId}/revenue`)
  },
  getClusterVillagesRevenue(clusterId, terms) {
    return Client.get(`${resource}/${clusterId}/villages-revenue${terms}`)
  },
  getAllRevenues(terms) {
    return Client.get(`${resource}/revenue${terms}`)
  },
  getClusterTrends(clusterId, terms) {
    return Client.get(`${resource}/${clusterId}/revenue/analysis${terms}`)
  },
}
