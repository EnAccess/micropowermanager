import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/kelin-meters/kelin-meter/minutely-consumptions`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
