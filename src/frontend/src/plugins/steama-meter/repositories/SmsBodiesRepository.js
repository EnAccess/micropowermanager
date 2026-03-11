import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/steama-meters/steama-setting/sms-setting/sms-body`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(smsBodies) {
    return Client.put(`${resource}`, smsBodies)
  },
}
