import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/manufacturers`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  search() {},
}
