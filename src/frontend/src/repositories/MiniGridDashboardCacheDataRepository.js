import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/dashboard/mini-grids`

export default {
  list() {
    return Client.get(`${resource}`)
  },

  update(from = null, to = null) {
    if (from !== null && to !== null) {
      return Client.put(`${resource}?from=${from}&to=${to}`)
    }
    return Client.put(`${resource}`)
  },

  detail(id) {
    return Client.get(`${resource}/${id}`)
  },
}
