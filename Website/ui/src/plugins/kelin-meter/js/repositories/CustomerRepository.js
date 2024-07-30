import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/kelin-meters/kelin-customer`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  sync() {
    return Client.get(`${resource}/sync`)
  },
  get(customerId) {
    return Client.get(`${resource}/${customerId}`)
  },
  syncCheck() {
    return Client.get(`${resource}/sync-check`)
  },

  update(customer) {
    return Client.put(`${resource}/${customer.id}`, customer)
  },
}
