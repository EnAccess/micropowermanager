import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/import`

export default {
  importAppliances(data) {
    return Client.post(`${resource}/appliances`, { data })
  },
}
