import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/spark-meters/sm-setting/sms-setting/sms-body`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(smsBodies) {
    return Client.put(`${resource}`, smsBodies)
  },
}
