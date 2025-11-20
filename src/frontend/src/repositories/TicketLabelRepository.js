import Client from "@/repositories/Client/AxiosClient"

const resource = `/tickets/api/labels`

export default {
  list() {
    return Client.get(`${resource}`)
  },

  create(labelPM) {
    return Client.post(`${resource}`, labelPM)
  },
}
