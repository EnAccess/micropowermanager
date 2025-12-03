import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/tickets`

export default {
  list() {
    return Client.get(`${resource}/users`)
  },

  create(user) {
    return Client.post(`${resource}/users`, user)
  },

  createExternal(user) {
    return Client.post(`${resource}/users/external`, user)
  },
}
