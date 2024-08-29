import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/spark-meters/sm-sales-account`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  sync() {
    return Client.get(`${resource}/sync`)
  },
  syncCheck() {
    return Client.get(`${resource}/sync-check`)
  },
  count() {
    return Client.get(`${resource}/count`)
  },
}
