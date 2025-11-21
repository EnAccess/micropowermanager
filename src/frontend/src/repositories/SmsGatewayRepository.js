import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/settings`

export default {
  getAvailableGateways() {
    return Client.get(`${resource}/sms-gateways`)
  },
}
