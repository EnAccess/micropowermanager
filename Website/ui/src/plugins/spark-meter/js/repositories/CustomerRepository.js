import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/spark-meters/sm-customer`

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

  connections() {
    return Client.get(`${resource}/connection`)
  },
  update(customer) {
    return Client.put(`${resource}/${customer.id}`, customer)
  },
}
