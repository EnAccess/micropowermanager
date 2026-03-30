import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/import`

export default {
  importSettings(data) {
    return Client.post(`${resource}/settings`, { data })
  },
}
