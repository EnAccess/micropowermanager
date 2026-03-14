import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/villages`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(village) {
    return Client.post(`${resource}`, village)
  },
}
