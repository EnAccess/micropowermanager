import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/targets`

export default {
  store(target) {
    return Client.post(`${resource}`, target)
  },
}
