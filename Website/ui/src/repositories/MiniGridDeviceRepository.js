import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/mini-grids`

export default {
  list(miniGridId) {
    return Client.get(`${resource}/${miniGridId}/devices`)
  },
}
