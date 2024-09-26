import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/meters`

export default {
  update(meterId, params) {
    return Client.put(`${resource}/${meterId}/parameters/`, params)
  },
}
