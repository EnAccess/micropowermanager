import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/steama-meters/steama-setting/sms-setting/sms-variable-default-value`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
