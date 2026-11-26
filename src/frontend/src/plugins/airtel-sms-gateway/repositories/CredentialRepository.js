import Client, { baseUrl } from "@/repositories/Client/AxiosClient.js"

const resource = `${baseUrl}/api/airtel-sms-gateway/credential`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  update(credentials) {
    return Client.put(`${resource}`, credentials)
  },
}
