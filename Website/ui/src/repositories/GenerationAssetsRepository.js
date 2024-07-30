import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/generation-assets`

export default {
  list(miniGridId, params) {
    return Client.get(`${resource}/${miniGridId}/readings`, {
      params: params,
    })
  },
}
