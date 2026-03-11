import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/sms-variable-default-value`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
