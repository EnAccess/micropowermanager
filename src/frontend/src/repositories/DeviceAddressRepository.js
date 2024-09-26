import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/device-addresses`

export default {
  update(params) {
    return Client.post(`${resource}`, params)
  },
}
