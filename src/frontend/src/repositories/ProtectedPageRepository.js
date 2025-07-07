import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

export const resource = `${baseUrl}/api/protected-pages`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  compare(data) {
    return Client.post(`${resource}/compare`, data)
  },
}
