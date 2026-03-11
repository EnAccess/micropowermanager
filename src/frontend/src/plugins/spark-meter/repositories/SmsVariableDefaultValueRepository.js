import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/spark-meters/sm-setting/sms-setting/sms-variable-default-value`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
