import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/clusters`

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
