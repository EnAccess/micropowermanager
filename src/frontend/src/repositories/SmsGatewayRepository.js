import Client, { baseUrl } from "@/repositories/Client/AxiosClient.js"

const resource = `${baseUrl}/api/settings`

export default {
  getAvailableGateways() {
    return Client.get(`${resource}/sms-gateways`)
  },
}
