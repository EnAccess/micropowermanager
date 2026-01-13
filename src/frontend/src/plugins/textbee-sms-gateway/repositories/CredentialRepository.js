import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/textbee-sms-gateway/credential`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  update(credentials) {
    return Client.put(`${resource}`, credentials)
  },
}
