import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/import`

export default {
  importSettings(data) {
    return Client.post(`${resource}/settings`, { data })
  },
}
