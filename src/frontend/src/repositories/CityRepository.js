import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/cities`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(city) {
    return Client.post(`${resource}`, city)
  },
}
