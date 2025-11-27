import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/tickets/labels`

export default {
  list() {
    return Client.get(`${resource}`)
  },

  create(labelPM) {
    return Client.post(`${resource}`, labelPM)
  },
}
