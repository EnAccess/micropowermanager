import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/tickets`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  detail(id) {
    return Client.get(`${resource}/${id}`)
  },
}
