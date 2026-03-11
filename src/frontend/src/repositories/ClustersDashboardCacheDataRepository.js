import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/dashboard/clusters`

export default {
  list() {
    return Client.get(`${resource}`)
  },

  update() {
    return Client.put(`${resource}`)
  },

  detail(id) {
    return Client.get(`${resource}/${id}`)
  },
}
