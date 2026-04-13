import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/import`

export default {
  importDevices(data) {
    return Client.post(`${resource}/devices`, { data })
  },
}
