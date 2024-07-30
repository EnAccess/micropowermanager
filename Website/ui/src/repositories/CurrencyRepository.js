import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/settings/currency-list`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
