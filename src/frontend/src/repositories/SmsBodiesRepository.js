import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/sms-body`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(smsBodies) {
    return Client.put(`${resource}`, smsBodies)
  },
}
