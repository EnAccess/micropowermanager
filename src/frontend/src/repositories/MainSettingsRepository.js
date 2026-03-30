import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/settings`

export default {
  list() {
    return Client.get(`${resource}/main`)
  },
  update(id, mainSettings) {
    return Client.put(`${resource}/main/${id}`, mainSettings)
  },
}
