import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

export const resource = `${baseUrl}/api/mpm-plugins`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
