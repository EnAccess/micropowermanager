import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/time-of-usages`

export default {
  delete(id) {
    return Client.delete(`${resource}/${id}`)
  },
}
