import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/spark-meters/sm-setting/sync-setting`

export default {
  update(syncListPM) {
    return Client.put(`${resource}`, syncListPM)
  },
}
