import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/cities`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(city) {
    return Client.post(`${resource}`, city)
  },
}
