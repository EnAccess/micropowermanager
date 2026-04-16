import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/clusters`

export default {
  create(cluster) {
    return Client.post(`${resource}`, cluster)
  },
  update(clusterId, cluster) {
    return Client.put(`${resource}/${clusterId}`, cluster)
  },
  delete(clusterId) {
    return Client.delete(`${resource}/${clusterId}`)
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
  getClusterCitiesRevenue(clusterId, terms) {
    return Client.get(`${resource}/${clusterId}/cities-revenue${terms}`)
  },
  getAllRevenues(terms) {
    return Client.get(`${resource}/revenue${terms}`)
  },
  getClusterTrends(clusterId, terms) {
    return Client.get(`${resource}/${clusterId}/revenue/analysis${terms}`)
  },
}
