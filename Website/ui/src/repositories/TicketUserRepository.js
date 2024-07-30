import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/tickets/api`

export default {
  list() {
    return Client.get(`${resource}/users`)
  },

  create(user) {
    return Client.post(`${resource}/users`, user)
  },

  createExternal(user) {
    return Client.post(`${resource}/users/external`, user)
  },
}
