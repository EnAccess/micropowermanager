import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/kelin-meters/kelin-meter/status`

export default {
  show(meterId) {
    return Client.get(`${resource}/${meterId}`)
  },
  update(statusPM) {
    return Client.put(`${resource}/${statusPM.meterId}`, statusPM)
  },
}
