import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/spark-meters/sm-setting`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
