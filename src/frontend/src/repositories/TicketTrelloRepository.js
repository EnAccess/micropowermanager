import Client from "@/repositories/Client/AxiosClient"

const resource = `/tickets`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  detail(id) {
    return Client.get(`${resource}/${id}`)
  },
}
